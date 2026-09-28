<?php

namespace App\Support\Sheets;

use App\Models\Group;
use App\Models\Region;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use Illuminate\Validation\Rule;

class TeacherSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'guru.xlsx';
    }

    public function headings(): array
    {
        return ['registration_number', 'name', 'gender', 'phone', 'email', 'village_name', 'group_name', 'status', 'notes'];
    }

    public function rows(): iterable
    {
        foreach (Teacher::visibleTo($this->user)->with(['village', 'group'])->orderBy('id')->cursor() as $teacher) {
            yield [
                $teacher->registration_number,
                $teacher->name,
                $teacher->gender,
                $teacher->phone,
                $teacher->email,
                $teacher->village?->name,
                $teacher->group?->name,
                $teacher->status,
                $teacher->notes,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        $existing = filled($row['registration_number'] ?? null)
            ? Teacher::where('registration_number', trim($row['registration_number']))->first()
            : null;

        [$data, $error] = $this->validated($row, [
            'registration_number' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['laki-laki', 'perempuan'])],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('teachers', 'email')->ignore($existing?->id)],
            'village_name' => ['required', 'string', 'max:255'],
            'group_name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $village = Village::whereRaw('lower(name) = ?', [mb_strtolower(trim($data['village_name']))])->where('is_active', true)->first();
        $group = $village === null ? null : Group::where('village_id', $village->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower(trim($data['group_name']))])->where('is_active', true)->first();

        if ($village === null || $group === null) {
            return 'Desa atau kelompok tidak ditemukan atau tidak aktif.';
        }

        $regionId = Region::karawangTimur()->id;

        if ($this->user !== null && ! $this->user->coversPlacement('manage-teachers', $regionId, $village->id, $group->id)) {
            return 'Penempatan berada di luar wilayah akses Anda.';
        }

        $attributes = [
            'name' => trim($data['name']),
            'gender' => $data['gender'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'region_id' => $regionId,
            'village_id' => $village->id,
            'group_id' => $group->id,
            'status' => self::parseActive($data['status'] ?? 'active') ? 'active' : 'inactive',
            'notes' => $data['notes'] ?? null,
        ];

        if ($existing !== null) {
            $existing->update($attributes);

            return null;
        }

        Teacher::create([
            ...$attributes,
            'registration_number' => filled($data['registration_number'] ?? null) ? trim($data['registration_number']) : Teacher::nextRegistrationNumber(),
        ]);

        return null;
    }
}
