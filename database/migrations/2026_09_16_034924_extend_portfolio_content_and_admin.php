<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });
        Schema::table('projects', function (Blueprint $table) {
            foreach (['target_users', 'key_features', 'database_design', 'api_architecture', 'lessons_learned', 'future_improvements'] as $field) {
                $table->longText($field)->nullable();
            }
            $table->foreignId('architecture_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('database_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->softDeletesTz();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE projects DROP CONSTRAINT IF EXISTS projects_status_check');
            DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK (status IN ('planned','in_progress','completed','maintained','archived','prototype','research'))");
        }
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('projects', function (Blueprint $table) {
                $table->enum('status', ['planned', 'in_progress', 'completed', 'maintained', 'archived', 'prototype', 'research'])->nullable()->change();
            });
        }
        Schema::table('skills', function (Blueprint $table) {
            $table->string('icon')->nullable();
            $table->string('proficiency', 80)->nullable();
        });
        Schema::table('profiles', function (Blueprint $table) {
            $table->longText('focus')->nullable();
            $table->longText('learning')->nullable();
            $table->longText('philosophy')->nullable();
        });
        Schema::table('experiences', function (Blueprint $table) {
            $table->longText('responsibilities')->nullable();
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
        });
        Schema::create('experience_technology', function (Blueprint $table) {
            $table->foreignId('experience_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->index()->constrained()->cascadeOnDelete();
            $table->primary(['experience_id', 'technology_id']);
        });
        Schema::table('educations', function (Blueprint $table) {
            $table->longText('coursework')->nullable();
        });
        Schema::table('certifications', function (Blueprint $table) {
            $table->foreignId('pdf_media_id')->nullable()->constrained('media')->nullOnDelete();
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->text('repository_url')->nullable();
            $table->softDeletesTz();
        });
        Schema::create('article_technology', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->index()->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'technology_id']);
        });
        Schema::table('media', function (Blueprint $table) {
            $table->string('folder')->default('portfolio/projects');
        });
    }

    public function down(): void
    {
        Schema::table('media', fn (Blueprint $table) => $table->dropColumn('folder'));
        Schema::dropIfExists('article_technology');
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['repository_url', 'deleted_at']);
        });
        Schema::table('certifications', fn (Blueprint $table) => $table->dropConstrainedForeignId('pdf_media_id'));
        Schema::table('educations', fn (Blueprint $table) => $table->dropColumn('coursework'));
        Schema::dropIfExists('experience_technology');
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logo_media_id');
            $table->dropColumn('responsibilities');
        });
        Schema::table('profiles', fn (Blueprint $table) => $table->dropColumn(['focus', 'learning', 'philosophy']));
        Schema::table('skills', fn (Blueprint $table) => $table->dropColumn(['icon', 'proficiency']));
        if (DB::getDriverName() === 'pgsql') {
            DB::table('projects')->whereIn('status', ['prototype', 'research'])->update(['status' => null]);
            DB::statement('ALTER TABLE projects DROP CONSTRAINT IF EXISTS projects_status_check');
            DB::statement("ALTER TABLE projects ADD CONSTRAINT projects_status_check CHECK (status IN ('planned','in_progress','completed','maintained','archived'))");
        }
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('architecture_media_id');
            $table->dropConstrainedForeignId('database_media_id');
            $table->dropColumn(['target_users', 'key_features', 'database_design', 'api_architecture', 'lessons_learned', 'future_improvements', 'deleted_at']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
