<?php

namespace App\Support\Sheets;

use App\Models\ClassGrade;

class ClassGradeSheet extends OrderedCodeSheet
{
    public function filename(): string
    {
        return 'kelas.xlsx';
    }

    protected function model(): string
    {
        return ClassGrade::class;
    }
}
