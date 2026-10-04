<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    public const IMAGE_DIRECTORY = 'announcement-images';

    /**
     * Template keys available for presenting an announcement on the dashboard.
     *
     * @var array<string, string>
     */
    public const TEMPLATES = [
        'default' => 'Standar',
        'highlight' => 'Sorotan',
        'image-left' => 'Gambar di Kiri',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'image',
        'template',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function imageUrl(): ?string
    {
        return $this->image !== null ? asset('storage/'.$this->image) : null;
    }
}
