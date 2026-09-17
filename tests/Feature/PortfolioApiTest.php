<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Media;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\Resume;
use App\Models\SiteSetting;
use App\Models\Technology;
use App\Models\User;
use Database\Seeders\PortfolioSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    public function test_admin_category_references_resolve_to_available_tables(): void
    {
        $definitions = config('content');
        $tables = array_column($definitions, 'table');
        $tables[] = 'media';
        foreach ($definitions as $resource => $definition) {
            foreach ($definition['fields'] as $field => $spec) {
                if (isset($spec['reference'])) {
                    $this->assertContains($spec['reference'], $tables, "$resource.$field must reference an available admin resource");
                    $this->assertTrue(Schema::hasTable($spec['reference']));
                }
            }
        }
    }

    public function test_admin_can_create_and_recategorize_skills(): void
    {
        $this->actingAs($this->admin());
        $backend = $this->postJson('/api/v1/admin/skill-categories', ['name' => 'Backend', 'slug' => 'backend'])->assertCreated()->json('data.id');
        $frontend = $this->postJson('/api/v1/admin/skill-categories', ['name' => 'Frontend', 'slug' => 'frontend'])->assertCreated()->json('data.id');
        $id = $this->postJson('/api/v1/admin/skills', ['name' => 'Example', 'slug' => 'example', 'skill_category_id' => $backend])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('skills', ['id' => $id, 'skill_category_id' => $backend]);
        $this->patchJson('/api/v1/admin/skills/'.$id, ['skill_category_id' => $frontend])->assertOk();
        $this->assertDatabaseHas('skills', ['id' => $id, 'skill_category_id' => $frontend]);
        $this->patchJson('/api/v1/admin/skills/'.$id, ['skill_category_id' => 999999])->assertUnprocessable()->assertJsonValidationErrors('skill_category_id');
    }

    public function test_public_projects_hide_drafts_future_and_deleted_content(): void
    {
        Project::factory()->create(['slug' => 'visible', 'published_at' => now()->subMinute()]);
        Project::factory()->create(['slug' => 'draft']);
        Project::factory()->create(['slug' => 'future', 'published_at' => now()->addDay()]);
        $this->getJson('/api/v1/projects')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'visible');
        $this->getJson('/api/v1/projects/draft')->assertNotFound();
        $this->getJson('/api/v1/projects/future')->assertNotFound();
        Project::where('slug', 'visible')->first()->delete();
        $this->getJson('/api/v1/projects/visible')->assertNotFound();
    }

    public function test_admin_requires_administrator_and_can_manage_projects(): void
    {
        $this->getJson('/api/v1/admin/projects')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/api/v1/admin/projects')->assertForbidden();
        $this->actingAs($this->admin());
        $id = $this->postJson('/api/v1/admin/projects', ['title' => 'Example', 'slug' => 'example', 'summary' => 'A test project'])->assertCreated()->json('data.id');
        $this->putJson('/api/v1/admin/projects/'.$id, ['title' => 'Updated'])->assertOk()->assertJsonPath('data.title', 'Updated');
        $this->postJson('/api/v1/admin/projects', ['title' => 'Duplicate', 'slug' => 'example'])->assertUnprocessable();
        $this->postJson('/api/v1/admin/projects', ['title' => 'Invalid', 'slug' => 'bad', 'repository_url' => 'javascript:alert(1)'])->assertUnprocessable();
        $this->deleteJson('/api/v1/admin/projects/'.$id)->assertNoContent();
        $this->assertSoftDeleted('projects', ['id' => $id]);
    }

    public function test_admin_cannot_access_users_or_cross_article_types(): void
    {
        $this->actingAs($this->admin());
        $id = $this->postJson('/api/v1/admin/engineering', ['title' => 'Design', 'slug' => 'design'])->assertCreated()->json('data.id');
        $this->patchJson('/api/v1/admin/blog/'.$id, ['title' => 'Wrong'])->assertNotFound();
        $this->getJson('/api/v1/admin/users')->assertNotFound();
    }

    public function test_contact_is_validated_stored_and_not_publicly_readable(): void
    {
        $this->postJson('/api/v1/contact', ['email' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/v1/contact', ['name' => 'Visitor', 'email' => 'visitor@example.com', 'message' => 'I would like to discuss a role.', 'status' => 'replied'])->assertCreated();
        $this->assertDatabaseHas('contact_messages', ['email' => 'visitor@example.com', 'status' => 'unread']);
        $this->getJson('/api/v1/contact-messages')->assertNotFound();
    }

    public function test_login_requires_admin_and_logout_ends_session(): void
    {
        $admin = $this->admin();
        $this->postJson('/api/v1/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertUnprocessable();
        $this->postJson('/api/v1/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertOk();
        $this->getJson('/api/v1/admin/me')->assertOk();
        $this->postJson('/api/v1/admin/logout')->assertNoContent();
        $this->getJson('/api/v1/admin/me')->assertUnauthorized();
    }

    public function test_media_validates_files_and_stores_only_metadata(): void
    {
        config(['portfolio.media_disk' => 'public']);
        Storage::fake('public');
        $this->actingAs($this->admin());
        $this->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml'), 'folder' => 'portfolio/projects'], ['Accept' => 'application/json'])->assertUnprocessable();
        $response = $this->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->image('photo.png'), 'folder' => 'portfolio/projects', 'alt_text' => 'Screenshot'], ['Accept' => 'application/json'])->assertCreated();
        $media = Media::findOrFail($response->json('data.id'));
        Storage::disk('public')->assertExists($media->path);
        $project = Project::factory()->create(['cover_media_id' => $media->id]);
        $this->deleteJson('/api/v1/admin/media/'.$media->id)->assertUnprocessable();
        $project->forceDelete();
        $this->deleteJson('/api/v1/admin/media/'.$media->id)->assertNoContent();
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_profile_logo_is_public_and_cannot_be_deleted_while_in_use(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $media = $this->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->image('logo.png'), 'folder' => 'portfolio/branding'], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        $this->postJson('/api/v1/admin/profile', ['name' => 'Chimeng Ly', 'slug' => 'chimeng-ly', 'logo_media_id' => $media])->assertCreated();
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.0.logo.id', $media);
        $this->deleteJson('/api/v1/admin/media/'.$media)->assertUnprocessable();
    }

    public function test_resume_visibility_controls_public_access_without_a_schedule(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $media = $this->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'), 'folder' => 'portfolio/resume'], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        $id = $this->postJson('/api/v1/admin/resumes', ['title' => 'My CV', 'media_id' => $media, 'is_visible' => true])->assertCreated()->json('data.id');
        Resume::findOrFail($id)->update(['published_at' => now()->addWeek()]);
        $this->getJson('/api/v1/resumes')->assertOk()->assertJsonPath('data.0.media.id', $media);
        $this->get('/api/v1/resumes/'.$id.'/download')->assertOk()->assertDownload('cv.pdf');
        $this->get('/api/v1/resumes/'.$id.'/file')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->patchJson('/api/v1/admin/resumes/'.$id, ['is_visible' => false])->assertOk();
        $this->getJson('/api/v1/resumes')->assertOk()->assertJsonCount(0, 'data');
        $this->get('/api/v1/resumes/'.$id.'/download')->assertNotFound();
        $this->get('/api/v1/resumes/'.$id.'/file')->assertNotFound();
        $this->patchJson('/api/v1/admin/resumes/'.$id, ['is_visible' => true])->assertOk();
        $this->getJson('/api/v1/resumes')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/admin/schema')->assertOk()->assertJsonMissingPath('data.resumes.fields.published_at');
    }

    public function test_private_settings_and_scheduled_articles_are_hidden(): void
    {
        SiteSetting::create(['key' => 'private', 'value' => ['note' => 'private'], 'is_public' => false]);
        Article::create(['title' => 'Later', 'slug' => 'later', 'type' => 'blog', 'published_at' => now()->addDay()]);
        $this->getJson('/api/v1/site-settings')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/blog/later')->assertNotFound();
    }

    public function test_seed_is_idempotent_and_does_not_invent_certificates_or_admins(): void
    {
        $this->seed(PortfolioSeeder::class);
        $this->seed(PortfolioSeeder::class);
        $this->assertDatabaseCount('projects', 7);
        $this->assertDatabaseCount('certifications', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertSame(0, Project::whereNotNull('published_at')->count());
    }

    public function test_csrf_protects_admin_mutations(): void
    {
        $this->app->bind(PreventRequestForgery::class, function ($app) {
            return new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });
        $this->postJson('/api/v1/admin/login', ['email' => 'test@example.com', 'password' => 'incorrect'])->assertStatus(419);
        $this->withSession(['_token' => 'test-csrf-token'])->withHeader('X-CSRF-TOKEN', 'test-csrf-token')
            ->postJson('/api/v1/admin/login', ['email' => 'test@example.com', 'password' => 'incorrect'])->assertUnprocessable();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/admin/login', ['email' => 'limited@example.com', 'password' => 'incorrect'])->assertUnprocessable();
        }
        $this->postJson('/api/v1/admin/login', ['email' => 'limited@example.com', 'password' => 'incorrect'])->assertTooManyRequests();
    }

    public function test_project_statuses_and_partial_date_updates_are_validated(): void
    {
        $this->actingAs($this->admin());
        $id = $this->postJson('/api/v1/admin/projects', ['title' => 'Prototype', 'slug' => 'prototype', 'status' => 'prototype', 'started_on' => '2026-01-01'])->assertCreated()->json('data.id');
        $this->patchJson('/api/v1/admin/projects/'.$id, ['ended_on' => '2025-01-01'])->assertUnprocessable();
        $this->patchJson('/api/v1/admin/projects/'.$id, ['ended_on' => '2026-02-01'])->assertOk();
        $this->patchJson('/api/v1/admin/projects/'.$id, ['started_on' => '2026-03-01'])->assertUnprocessable();
    }

    public function test_resume_requires_pdf_and_galleries_require_images(): void
    {
        $this->actingAs($this->admin());
        $image = Media::create(['disk' => 'public', 'path' => 'image.png', 'original_name' => 'image.png', 'mime_type' => 'image/png', 'size' => 100]);
        $pdf = Media::create(['disk' => 'public', 'path' => 'resume.pdf', 'original_name' => 'resume.pdf', 'mime_type' => 'application/pdf', 'size' => 100]);
        $this->postJson('/api/v1/admin/resumes', ['title' => 'Resume', 'media_id' => $image->id])->assertUnprocessable();
        $this->postJson('/api/v1/admin/resumes', ['title' => 'Resume', 'media_id' => $pdf->id])->assertCreated();
        $this->postJson('/api/v1/admin/projects', ['title' => 'Test', 'slug' => 'gallery-test', 'media_ids' => [$pdf->id]])->assertUnprocessable();
    }

    public function test_category_technology_filters_and_pagination(): void
    {
        $category = ProjectCategory::create(['name' => 'Backend', 'slug' => 'backend']);
        $technology = Technology::create(['name' => 'Laravel', 'slug' => 'laravel']);
        $project = Project::factory()->create(['project_category_id' => $category->id, 'published_at' => now()->subMinute(), 'is_featured' => true]);
        $project->technologies()->attach($technology);
        Project::factory()->create(['published_at' => now()->subMinute()]);
        $this->getJson('/api/v1/projects?category=backend&technology=laravel')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $project->id);
        $this->getJson('/api/v1/projects?per_page=1')->assertOk()->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/v1/projects/featured')->assertOk()->assertJsonCount(1, 'data');
    }
}
