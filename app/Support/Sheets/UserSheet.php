<?php

namespace App\Support\Sheets;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Password hashes are never exported, and role/scope assignment stays a manual step in the
 * Data Peran UI: granting a role with the wrong scope through a spreadsheet is too easy to
 * get wrong silently. Import only touches name, email, status, and (when given) a new password.
 */
class UserSheet extends Sheet
{
    public function filename(): string
    {
        return 'pengguna.xlsx';
    }

    public function headings(): array
    {
        return ['name', 'email', 'password', 'status', 'role_names'];
    }

    public function rows(): iterable
    {
        foreach (User::with('roles')->orderBy('name')->cursor() as $user) {
            yield [
                $user->name,
                $user->email,
                null,
                $user->status,
                $user->roles->pluck('name')->implode(', '),
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        $existing = filled($row['email'] ?? null) ? User::where('email', trim($row['email']))->first() : null;

        [$data, $error] = $this->validated($row, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [$existing ? 'nullable' : 'required', 'string', 'min:8'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $attributes = [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'status' => self::parseActive($data['status']) ? 'active' : 'inactive',
        ];

        if (filled($data['password'] ?? null)) {
            $attributes['password'] = Hash::make($data['password']);
        }

        if ($existing !== null) {
            $existing->update($attributes);

            return null;
        }

        User::create($attributes);

        return null;
    }
}
