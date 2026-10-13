<?php

namespace App\Support\Sheets;

use App\Models\Permission;
use App\Models\Role;

class RoleSheet extends Sheet
{
    public function filename(): string
    {
        return 'peran.xlsx';
    }

    public function headings(): array
    {
        return ['name', 'slug', 'description', 'status', 'permission_slugs'];
    }

    public function rows(): iterable
    {
        foreach (Role::with('permissions')->orderBy('name')->cursor() as $role) {
            yield [
                $role->name,
                $role->slug,
                $role->description,
                self::activeLabel($role->is_active),
                $role->permissions->pluck('slug')->implode(', '),
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'permission_slugs' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        if (trim($data['slug']) === 'super-admin') {
            return 'Peran Super Admin dikelola langsung oleh sistem dan tidak bisa diubah lewat import.';
        }

        $slugs = collect(explode(',', (string) ($data['permission_slugs'] ?? '')))
            ->map(fn (string $slug): string => trim($slug))
            ->filter()
            ->unique()
            ->values();

        $permissionIds = $slugs->isEmpty() ? collect() : Permission::whereIn('slug', $slugs)->pluck('id', 'slug');
        $missing = $slugs->diff($permissionIds->keys());

        if ($missing->isNotEmpty()) {
            return 'Slug permission tidak ditemukan: '.$missing->implode(', ').'.';
        }

        $role = Role::updateOrCreate(
            ['slug' => trim($data['slug'])],
            [
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'is_active' => self::parseActive($data['status'] ?? 'aktif'),
            ],
        );

        $role->permissions()->sync($permissionIds->values()->all());

        return null;
    }
}
