<?php

namespace App\Support\Sheets;

use App\Models\ActivityExecution;
use App\Models\ActivitySchedule;
use App\Models\OrganizationUnit;

class ActivityExecutionSheet extends Sheet
{
    public function filename(): string
    {
        return 'pelaksanaan-program.xlsx';
    }

    public function headings(): array
    {
        return [
            'schedule_organization_unit_code', 'schedule_title', 'schedule_scheduled_at',
            'actual_date', 'status', 'attendance_count', 'outcome', 'notes',
        ];
    }

    public function rows(): iterable
    {
        foreach (ActivityExecution::with('activitySchedule.organizationUnit')->orderBy('actual_date')->cursor() as $execution) {
            $schedule = $execution->activitySchedule;

            yield [
                $schedule?->organizationUnit?->code,
                $schedule?->title,
                $schedule?->scheduled_at,
                $execution->actual_date,
                $execution->status,
                $execution->attendance_count,
                $execution->outcome,
                $execution->notes,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'schedule_organization_unit_code' => ['required', 'string', 'max:50'],
            'schedule_title' => ['required', 'string', 'max:255'],
            'schedule_scheduled_at' => ['required', 'date'],
            'actual_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'attendance_count' => ['required', 'integer', 'min:0'],
            'outcome' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $organizationUnit = OrganizationUnit::where('code', trim($data['schedule_organization_unit_code']))->first();

        $schedule = $organizationUnit === null ? null : ActivitySchedule::where('organization_unit_id', $organizationUnit->id)
            ->where('title', trim($data['schedule_title']))
            ->whereDate('scheduled_at', $data['schedule_scheduled_at'])
            ->first();

        if ($schedule === null) {
            return 'Jadwal program tidak ditemukan untuk unit organisasi, judul, dan tanggal tersebut.';
        }

        ActivityExecution::updateOrCreate(
            ['activity_schedule_id' => $schedule->id, 'actual_date' => $data['actual_date']],
            [
                'status' => trim($data['status']),
                'attendance_count' => (int) $data['attendance_count'],
                'outcome' => trim($data['outcome']),
                'notes' => $data['notes'] ?? null,
            ],
        );

        return null;
    }
}
