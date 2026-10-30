<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DownloadFile extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_url',
        'sort_order',
        'is_active',
    ];
}
