<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $fillable = [
        'full_name',
        'relationship',
        'phone',
        'address',
        'status',
        'notes',
    ];
}
