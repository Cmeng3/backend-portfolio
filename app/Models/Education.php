<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Education extends Model
{
    protected $table = 'educations';

    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function pdf(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'pdf_media_id');
    }

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'is_featured' => 'boolean', 'is_visible' => 'boolean', 'is_public' => 'boolean', 'is_current' => 'boolean', 'sort_order' => 'integer'];
    }
}
