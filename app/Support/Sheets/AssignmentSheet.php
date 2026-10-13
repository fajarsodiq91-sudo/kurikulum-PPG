<?php

namespace App\Support\Sheets;

use App\Models\Assignment;
use App\Models\OrganizationUnit;
use App\Models\Teacher;

class AssignmentSheet extends Sheet
{
    public function filename(): string
    {
        return 'penempatan.xlsx';
    }

    public function headings(): array
    {
        return ['organization_unit_code', 'teacher_registration_number', 'assignment_title', 'assignment_type', 'start_date', 'end_date', 'status', 'responsibility'];
    }

    public function rows(): iterable
    {
        foreach (Assignment::with(['organizationUnit', 'teacher'])->orderBy('id')->cursor() as $assignment) {
            yield [
                $assignment->organizationUnit?->code,
                $assignment->teacher?->registration_number,
                $assignment->assignment_title,
                $assignment->assignment_type,
                $assignment->start_date,
                $assignment->end_date,
                $assignment->status,
                $assignment->responsibility,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'organization_unit_code' => ['required', 'string', 'max:50'],
            'teacher_registration_number' => ['required', 'string', 'max:50'],
            'assignment_title' => ['required', 'string', 'max:255'],
            'assignment_type' => ['required', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'responsibility' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $organizationUnit = OrganizationUnit::where('code', trim($data['organization_unit_code']))->first();
        $teacher = Teacher::where('registration_number', trim($data['teacher_registration_number']))->first();

        if ($organizationUnit === null || $teacher === null) {
            return 'Unit organisasi atau guru tidak ditemukan.';
        }

        Assignment::updateOrCreate(
            [
                'organization_unit_id' => $organizationUnit->id,
                'teacher_id' => $teacher->id,
                'assignment_title' => trim($data['assignment_title']),
                'assignment_type' => trim($data['assignment_type']),
                'start_date' => $data['start_date'],
            ],
            [
                'end_date' => $data['end_date'] ?? null,
                'status' => trim($data['status']),
                'responsibility' => $data['responsibility'] ?? null,
            ],
        );

        return null;
    }
}
