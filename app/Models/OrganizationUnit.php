<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationUnit extends Model
{
    protected $table = 'organization_units';

    protected $fillable = [
        'name',
        'code',
        'leader_name',
        'unit_type',
        'status',
        'description',
    ];
}
