<?php

namespace App\Support\Sheets;

use App\Models\ActivitySchedule;
use App\Models\OrganizationUnit;

class ActivityScheduleSheet extends Sheet
{
    public function filename(): string
    {
        return 'jadwal-program.xlsx';
    }

    public function headings(): array
    {
        return ['organization_unit_code', 'title', 'type', 'scheduled_at', 'end_at', 'location', 'status', 'notes'];
    }

    public function rows(): iterable
    {
        foreach (ActivitySchedule::with('organizationUnit')->orderBy('scheduled_at')->cursor() as $schedule) {
            yield [
                $schedule->organizationUnit?->code,
                $schedule->title,
                $schedule->type,
                $schedule->scheduled_at,
                $schedule->end_at,
                $schedule->location,
                $schedule->status,
                $schedule->notes,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'organization_unit_code' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'scheduled_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $organizationUnit = OrganizationUnit::where('code', trim($data['organization_unit_code']))->first();

        if ($organizationUnit === null) {
            return 'Unit organisasi tidak ditemukan.';
        }

        ActivitySchedule::updateOrCreate(
            [
                'organization_unit_id' => $organizationUnit->id,
                'title' => trim($data['title']),
                'type' => trim($data['type']),
                'scheduled_at' => $data['scheduled_at'],
            ],
            [
                'end_at' => $data['end_at'] ?? null,
                'location' => $data['location'] ?? null,
                'status' => trim($data['status']),
                'notes' => $data['notes'] ?? null,
            ],
        );

        return null;
    }
}
