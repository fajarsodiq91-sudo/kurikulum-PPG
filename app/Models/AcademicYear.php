<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'name',
        'code',
        'start_year',
        'end_year',
        'is_active',
    ];
}
