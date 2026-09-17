<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Related article pivots cascade; media files remain in the library.
        DB::table('articles')->where('type', 'engineering')->delete();
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['type', 'published_at']);
            $table->dropColumn('type');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        // Restores the old schema, not deleted Engineering content.
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->enum('type', ['blog', 'engineering'])->default('blog');
            $table->index(['type', 'published_at']);
        });
    }
};
