<?php

namespace App\Support\Sheets;

use Illuminate\Support\Facades\Validator;

/**
 * One spreadsheet-backed resource: what an export contains and how an imported row is applied.
 * The exported file is the import template, so headings are identical in both directions.
 */
abstract class Sheet
{
    abstract public function filename(): string;

    /**
     * @return list<string>
     */
    abstract public function headings(): array;

    /**
     * @return iterable<list<mixed>>
     */
    abstract public function rows(): iterable;

    /**
     * Applies one imported row (keys are the headings) and returns an error message, or null on success.
     *
     * @param  array<string, mixed>  $row
     */
    abstract public function importRow(array $row): ?string;

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $rules
     * @return array{0: array<string, mixed>|null, 1: string|null} Validated data, or an error message.
     */
    protected function validated(array $row, array $rules): array
    {
        $validator = Validator::make($row, $rules);

        return $validator->fails() ? [null, implode(' ', $validator->errors()->all())] : [$validator->validated(), null];
    }

    protected static function parseActive(mixed $value): bool
    {
        return ! in_array(mb_strtolower(trim((string) $value)), ['0', 'false', 'nonaktif', 'tidak aktif', 'inactive', 'tidak', 'no'], true);
    }

    protected static function activeLabel(bool $isActive): string
    {
        return $isActive ? 'aktif' : 'nonaktif';
    }
}
