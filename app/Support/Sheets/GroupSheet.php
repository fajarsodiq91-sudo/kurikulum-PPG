<?php

namespace App\Support\Sheets;

use App\Models\Group;
use App\Models\Village;

class GroupSheet extends Sheet
{
    public function filename(): string
    {
        return 'kelompok.xlsx';
    }

    public function headings(): array
    {
        return ['village_name', 'name', 'code', 'description', 'status'];
    }

    public function rows(): iterable
    {
        foreach (Group::with('village')->orderBy('name')->cursor() as $group) {
            yield [$group->village?->name, $group->name, $group->code, $group->description, self::activeLabel($group->is_active)];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'village_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $village = Village::whereRaw('lower(name) = ?', [mb_strtolower(trim($data['village_name']))])->first();

        if ($village === null) {
            return "Desa \"{$data['village_name']}\" tidak ditemukan.";
        }

        $group = Group::where('village_id', $village->id)->whereRaw('lower(name) = ?', [mb_strtolower(trim($data['name']))])->first()
            ?? new Group(['village_id' => $village->id]);

        $group->fill([
            'name' => trim($data['name']),
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => self::parseActive($data['status'] ?? 'aktif'),
        ])->save();

        return null;
    }
}
