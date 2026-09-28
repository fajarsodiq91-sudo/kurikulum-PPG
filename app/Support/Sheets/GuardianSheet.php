<?php

namespace App\Support\Sheets;

use App\Models\Guardian;
use App\Models\User;

class GuardianSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'orang-tua-wali.xlsx';
    }

    /**
     * generus_names is informational only; links are maintained from each generus' parent names.
     */
    public function headings(): array
    {
        return ['full_name', 'relationship', 'phone', 'address', 'status', 'notes', 'generus_names'];
    }

    public function rows(): iterable
    {
        foreach (Guardian::visibleTo($this->user)->with('generus:id,full_name')->orderBy('full_name')->cursor() as $guardian) {
            yield [
                $guardian->full_name,
                $guardian->relationship,
                $guardian->phone,
                $guardian->address,
                $guardian->status,
                $guardian->notes,
                $guardian->generus->pluck('full_name')->implode(', '),
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'full_name' => ['required', 'string', 'max:255'],
            'relationship' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $guardian = Guardian::whereRaw('lower(full_name) = ?', [mb_strtolower(trim($data['full_name']))])
            ->whereRaw('lower(relationship) = ?', [mb_strtolower(trim($data['relationship']))])
            ->first() ?? new Guardian;

        $guardian->fill([
            'full_name' => trim($data['full_name']),
            'relationship' => trim($data['relationship']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => self::parseActive($data['status'] ?? 'active') ? 'active' : 'inactive',
            'notes' => $data['notes'] ?? null,
        ])->save();

        return null;
    }
}
