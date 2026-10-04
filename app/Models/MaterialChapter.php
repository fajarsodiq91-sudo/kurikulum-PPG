<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialChapter extends Model
{
    protected $fillable = [
        'material_category_id',
        'name',
        'code',
        'sort_order',
        'is_active',
        'description',
    ];

    public function materialCategory(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class);
    }

    public function learningMaterials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class);
    }
}
