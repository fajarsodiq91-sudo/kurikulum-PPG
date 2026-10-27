<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dapukan extends Model
{
    protected $fillable = [
        'role_slug',
        'group_label',
        'name',
        'sort_order',
        'can_approve',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'can_approve' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
