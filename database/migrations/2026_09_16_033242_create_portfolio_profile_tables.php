<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('headline')->nullable();
            $table->text('introduction')->nullable();
            $table->longText('biography')->nullable();
            $table->string('location')->nullable();
            $table->string('public_email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('availability')->nullable();
            $table->foreignId('avatar_media_id')->nullable()->index()->constrained('media')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('social_links', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 80);
            $table->string('label');
            $table->text('url');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(false);
            $table->index(['is_visible', 'sort_order']);
            $table->timestampsTz();
        });

        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('media_id')->index()->constrained('media')->restrictOnDelete();
            $table->string('locale', 20)->default('en');
            $table->timestampTz('published_at')->nullable()->index();
            $table->timestampsTz();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('resumes');
        Schema::dropIfExists('social_links');
        Schema::dropIfExists('profiles');
    }
};
