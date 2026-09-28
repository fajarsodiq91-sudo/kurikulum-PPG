<?php

namespace App\Imports;

use App\Support\Sheets\Sheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SheetImport implements ToCollection, WithHeadingRow
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    public function __construct(private Sheet $sheet) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $data = collect($row->toArray())
                ->map(fn (mixed $value): mixed => is_int($value) || is_float($value) ? (string) $value : $value)
                ->all();

            if (collect($data)->filter(fn (mixed $value): bool => filled($value))->isEmpty()) {
                continue;
            }

            $rowNumber = $index + 2;

            if (($error = $this->sheet->importRow($data)) !== null) {
                $this->errors["row_{$rowNumber}"] = "Baris {$rowNumber}: {$error}";
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
