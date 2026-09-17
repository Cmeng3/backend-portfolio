<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->boolean('is_visible')->default(false)->index();
        });
        DB::table('resumes')->whereNotNull('published_at')->where('published_at', '<=', now())->update(['is_visible' => true]);
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropIndex(['is_visible']);
            $table->dropColumn('is_visible');
        });
    }
};
