<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('educations', function (Blueprint $table) {
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('pdf_media_id')->nullable()->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('educations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('media_id');
            $table->dropConstrainedForeignId('pdf_media_id');
        });
    }
};
