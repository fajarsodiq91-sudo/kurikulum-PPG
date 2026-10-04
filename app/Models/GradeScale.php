<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeScale extends Model
{
    protected $fillable = [
        'grade',
        'min_score',
        'max_score',
        'description',
        'sort_order',
        'is_active',
    ];

    /**
     * Finds the active scale whose range covers the given score, highest sort_order first.
     */
    public static function resolve(float $score): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('sort_order')
            ->first();
    }
}
