<?php

namespace App\Support\Sheets;

use App\Models\Dapukan;

class DapukanSheet extends Sheet
{
    public function filename(): string
    {
        return 'dapukan.xlsx';
    }

    public function headings(): array
    {
        return ['role_slug', 'group_label', 'name', 'sort_order', 'can_approve', 'status'];
    }

    public function rows(): iterable
    {
        foreach (Dapukan::orderBy('role_slug')->orderBy('sort_order')->cursor() as $dapukan) {
            yield [
                $dapukan->role_slug,
                $dapukan->group_label,
                $dapukan->name,
                $dapukan->sort_order,
                $dapukan->can_approve ? 'ya' : 'tidak',
                self::activeLabel($dapukan->is_active),
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'role_slug' => ['required', 'string', 'in:ppg,perwakilan-ppg-desa,pelaksana-ppg-kelompok'],
            'group_label' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'can_approve' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($error !== null) {
            return $error;
        }

        Dapukan::updateOrCreate(
            [
                'role_slug' => $data['role_slug'],
                'group_label' => filled($data['group_label'] ?? null) ? trim($data['group_label']) : null,
                'name' => trim($data['name']),
            ],
            [
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'can_approve' => self::parseActive($data['can_approve'] ?? 'tidak'),
                'is_active' => self::parseActive($data['status'] ?? 'aktif'),
            ],
        );

        return null;
    }
}
