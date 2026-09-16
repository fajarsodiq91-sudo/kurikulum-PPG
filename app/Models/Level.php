<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_active',
        'description',
    ];
}
