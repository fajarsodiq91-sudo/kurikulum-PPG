<?php

namespace App\Support\Sheets;

use App\Models\Region;
use App\Models\Village;

class VillageSheet extends Sheet
{
    public function filename(): string
    {
        return 'desa.xlsx';
    }

    public function headings(): array
    {
        return ['name', 'code', 'description', 'status'];
    }

    public function rows(): iterable
    {
        foreach (Village::orderBy('name')->cursor() as $village) {
            yield [$village->name, $village->code, $village->description, self::activeLabel($village->is_active)];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $village = Village::whereRaw('lower(name) = ?', [mb_strtolower(trim($data['name']))])->first()
            ?? new Village(['region_id' => Region::karawangTimur()->id]);

        $village->fill([
            'name' => trim($data['name']),
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => self::parseActive($data['status'] ?? 'aktif'),
        ])->save();

        return null;
    }
}
