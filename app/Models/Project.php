<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'is_featured' => 'boolean', 'is_visible' => 'boolean', 'is_public' => 'boolean', 'is_current' => 'boolean', 'sort_order' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function architectureImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'architecture_media_id');
    }

    public function databaseImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'database_media_id');
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'project_technology');
    }

    public function gallery(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'project_media')->withPivot('caption', 'sort_order')->orderByPivot('sort_order');
    }
}
