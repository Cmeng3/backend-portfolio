<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'is_featured' => 'boolean', 'is_visible' => 'boolean', 'is_public' => 'boolean', 'is_current' => 'boolean', 'sort_order' => 'integer'];
    }

    protected $table = 'media';

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        if ($this->disk === 's3' && config('portfolio.signed_media_urls')) {
            return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addHour());
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
