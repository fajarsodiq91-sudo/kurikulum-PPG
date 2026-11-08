<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'event_date',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'event_date' => 'date',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The hierarchy-picked audience for this announcement's email notification. Empty means
     * every active user (the original, un-targeted behaviour).
     */
    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_recipients');
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
