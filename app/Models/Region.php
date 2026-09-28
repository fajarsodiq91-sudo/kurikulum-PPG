<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'is_active',
        'description',
    ];

    public const DEFAULT_NAME = 'Karawang Timur';

    /**
     * The single region this system serves; created on first use.
     */
    public static function karawangTimur(): self
    {
        return self::firstOrCreate(['name' => self::DEFAULT_NAME], ['code' => 'KT', 'is_active' => true]);
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }
}
