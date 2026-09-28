<?php

namespace App\Exports;

use App\Support\Sheets\Sheet;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SheetExport implements FromGenerator, WithHeadings
{
    public function __construct(private Sheet $sheet) {}

    public function generator(): \Generator
    {
        yield from $this->sheet->rows();
    }

    public function headings(): array
    {
        return $this->sheet->headings();
    }
}
