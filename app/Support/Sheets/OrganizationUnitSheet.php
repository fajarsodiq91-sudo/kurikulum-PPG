<?php

namespace App\Support\Sheets;

use App\Models\OrganizationUnit;

class OrganizationUnitSheet extends Sheet
{
    public function filename(): string
    {
        return 'struktur-organisasi.xlsx';
    }

    public function headings(): array
    {
        return ['name', 'code', 'leader_name', 'unit_type', 'status', 'description'];
    }

    public function rows(): iterable
    {
        foreach (OrganizationUnit::orderBy('name')->cursor() as $unit) {
            yield [$unit->name, $unit->code, $unit->leader_name, $unit->unit_type, $unit->status, $unit->description];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50'],
            'leader_name' => ['nullable', 'string', 'max:255'],
            'unit_type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        OrganizationUnit::updateOrCreate(
            ['code' => trim($data['code'])],
            [
                'name' => trim($data['name']),
                'leader_name' => $data['leader_name'] ?? null,
                'unit_type' => trim($data['unit_type']),
                'status' => trim($data['status']),
                'description' => $data['description'] ?? null,
            ],
        );

        return null;
    }
}
