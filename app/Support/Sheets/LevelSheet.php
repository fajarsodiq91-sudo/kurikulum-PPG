<?php

namespace App\Support\Sheets;

use App\Models\Level;

class LevelSheet extends OrderedCodeSheet
{
    public function filename(): string
    {
        return 'jenjang.xlsx';
    }

    protected function model(): string
    {
        return Level::class;
    }
}
