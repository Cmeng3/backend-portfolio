<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicContentCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_reads_are_cached_without_repeating_queries(): void
    {
        Project::factory()->create(['slug' => 'cached', 'published_at' => now()->subMinute()]);
        $this->getJson('/api/v1/projects')->assertOk()->assertHeader('X-Portfolio-Cache', 'MISS');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->getJson('/api/v1/projects')->assertOk()->assertHeader('X-Portfolio-Cache', 'HIT');
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
        $this->getJson('/api/v1/projects?search=absent')->assertJsonCount(0, 'data');
    }

    public function test_admin_changes_invalidate_lists_and_detail_pages(): void
    {
        $project = Project::factory()->create(['slug' => 'visible', 'published_at' => now()->subMinute()]);
        $this->getJson('/api/v1/projects')->assertOk();
        $this->getJson('/api/v1/projects/visible')->assertOk();
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $this->actingAs($admin);
        $this->getJson('/api/v1/admin/projects')->assertHeaderMissing('X-Portfolio-Cache');
        $this->patchJson('/api/v1/admin/projects/'.$project->id, ['is_visible' => false])->assertOk();
        $this->getJson('/api/v1/projects')->assertJsonCount(0, 'data')->assertHeader('X-Portfolio-Cache', 'MISS');
        $this->getJson('/api/v1/projects/visible')->assertNotFound()->assertHeaderMissing('X-Portfolio-Cache');
    }

    public function test_cached_content_expires_and_can_be_disabled(): void
    {
        $this->getJson('/api/v1/projects')->assertHeader('X-Portfolio-Cache', 'MISS');
        $this->travel(61)->seconds();
        $this->getJson('/api/v1/projects')->assertHeader('X-Portfolio-Cache', 'MISS');
        config(['portfolio.public_cache_seconds' => 0]);
        $this->getJson('/api/v1/projects')->assertHeaderMissing('X-Portfolio-Cache');
    }
}
