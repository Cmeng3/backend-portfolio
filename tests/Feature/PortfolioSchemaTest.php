<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PortfolioSchemaTest extends TestCase
{
    use DatabaseMigrations;

    public function test_all_portfolio_tables_exist_and_content_starts_empty(): void
    {
        foreach (['media', 'profiles', 'social_links', 'resumes', 'site_settings', 'skill_categories', 'skills', 'project_categories', 'technologies', 'projects', 'project_technology', 'project_media', 'experiences', 'educations', 'certifications', 'article_categories', 'tags', 'articles', 'article_tag', 'article_media', 'contact_messages'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
            $this->assertDatabaseCount($table, 0);
        }

        $this->seed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_new_content_is_unpublished_and_messages_are_unread(): void
    {
        $project = DB::table('projects')->insertGetId(['title' => 'Draft', 'slug' => 'draft']);
        $article = DB::table('articles')->insertGetId(['title' => 'Note', 'slug' => 'note', 'type' => 'engineering']);
        $message = DB::table('contact_messages')->insertGetId(['name' => 'Visitor', 'email' => 'visitor@example.com', 'message' => 'Hello']);

        $this->assertDatabaseHas('projects', ['id' => $project, 'published_at' => null, 'status' => null, 'is_featured' => false]);
        $this->assertDatabaseHas('articles', ['id' => $article, 'published_at' => null]);
        $this->assertDatabaseHas('contact_messages', ['id' => $message, 'status' => 'unread']);
    }

    public function test_project_slugs_must_be_unique(): void
    {
        DB::table('projects')->insert(['title' => 'First', 'slug' => 'duplicate']);

        $this->expectException(QueryException::class);
        DB::table('projects')->insert(['title' => 'Second', 'slug' => 'duplicate']);
    }

    public function test_project_technology_pairs_cannot_be_duplicated(): void
    {
        $project = DB::table('projects')->insertGetId(['title' => 'Example', 'slug' => 'example']);
        $technology = DB::table('technologies')->insertGetId(['name' => 'PHP', 'slug' => 'php']);
        $link = ['project_id' => $project, 'technology_id' => $technology];
        DB::table('project_technology')->insert($link);

        $this->expectException(QueryException::class);
        DB::table('project_technology')->insert($link);
    }

    public function test_foreign_keys_reject_orphaned_skills(): void
    {
        $this->expectException(QueryException::class);
        DB::table('skills')->insert(['name' => 'PHP', 'slug' => 'php', 'skill_category_id' => 999]);
    }

    public function test_deleting_categories_and_projects_preserves_independent_records(): void
    {
        $category = DB::table('project_categories')->insertGetId(['name' => 'Web', 'slug' => 'web']);
        $project = DB::table('projects')->insertGetId(['title' => 'Example', 'slug' => 'example', 'project_category_id' => $category]);
        $technology = DB::table('technologies')->insertGetId(['name' => 'PHP', 'slug' => 'php']);
        DB::table('project_technology')->insert(['project_id' => $project, 'technology_id' => $technology]);

        DB::table('project_categories')->where('id', $category)->delete();
        $this->assertDatabaseHas('projects', ['id' => $project, 'project_category_id' => null]);

        DB::table('projects')->where('id', $project)->delete();
        $this->assertDatabaseCount('project_technology', 0);
        $this->assertDatabaseHas('technologies', ['id' => $technology]);
    }

    public function test_resume_media_cannot_be_deleted_while_referenced(): void
    {
        $media = DB::table('media')->insertGetId(['path' => 'resume/example.pdf', 'original_name' => 'example.pdf', 'mime_type' => 'application/pdf', 'size' => 100]);
        DB::table('resumes')->insert(['title' => 'Resume', 'media_id' => $media]);

        $this->expectException(QueryException::class);
        DB::table('media')->where('id', $media)->delete();
    }

    public function test_migrations_can_be_rolled_back_and_reapplied(): void
    {
        $this->artisan('migrate:reset', ['--force' => true])->assertExitCode(0);
        $this->assertFalse(Schema::hasTable('projects'));
        $this->assertFalse(Schema::hasTable('media'));
        $this->assertFalse(Schema::hasTable('articles'));
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->assertTrue(Schema::hasTable('projects'));
        $this->assertTrue(Schema::hasTable('articles'));
    }
}
