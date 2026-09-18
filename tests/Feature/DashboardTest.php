<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\DashboardController;
use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Project;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_respect_publication_and_soft_deletes_with_three_database_round_trips(): void
    {
        Project::factory()->create(['is_featured' => true]);
        Project::factory()->create(['is_featured' => false]);
        Project::factory()->create(['is_featured' => true])->delete();
        foreach (['live' => now()->subDay(), 'future' => now()->addDay(), 'draft' => null, 'deleted' => now()->subDay()] as $slug => $date) {
            $article = Article::create(['title' => $slug, 'slug' => $slug, 'published_at' => $date]);
            if ($slug === 'deleted') {
                $article->delete();
            }
        }
        $category = SkillCategory::create(['name' => 'Backend', 'slug' => 'backend']);
        Skill::create(['name' => 'PHP', 'slug' => 'php', 'skill_category_id' => $category->id]);
        foreach (['unread', 'read'] as $status) {
            ContactMessage::create(['name' => 'Visitor', 'email' => 'visitor@example.com', 'message' => 'Private message', 'admin_notes' => 'Private notes', 'status' => $status]);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $result = app(DashboardController::class)()['data'];
            $this->assertCount(3, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
        $this->assertSame(['Projects' => 2, 'Featured projects' => 1, 'Published blog posts' => 1, 'Skills' => 1, 'Contact messages' => 2, 'Unread messages' => 1], $result['counts']);
        $this->assertCount(2, $result['projects']);
        $this->assertArrayNotHasKey('message', $result['messages'][0]->toArray());
        $this->assertArrayNotHasKey('admin_notes', $result['messages'][0]->toArray());
    }

    public function test_dashboard_remains_private(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    }
}
