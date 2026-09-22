<?php

namespace App\Exports;

use App\Models\Generus;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GenerusExport implements FromQuery, WithHeadings, WithMapping
{
    public function query(): Builder
    {
        return Generus::query()
            ->with([
                'assignments' => fn ($query) => $query
                    ->where('status', 'active')
                    ->latest('assigned_at')
                    ->with(['region', 'village', 'group', 'level', 'academicYear']),
            ])
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'registration_number',
            'record_number',
            'full_name',
            'school_name',
            'nis',
            'father_name',
            'mother_name',
            'father_occupation',
            'mother_occupation',
            'phone_number',
            'gender',
            'birth_place',
            'birth_date',
            'birth_order',
            'sibling_count',
            'school_grade',
            'learning_class',
            'educational_level',
            'status',
            'notes',
            'region_code',
            'village_code',
            'group_code',
            'level_code',
            'academic_year_code',
            'assignment_status',
            'assignment_notes',
        ];
    }

    public function map($generus): array
    {
        $assignment = $generus->assignments->first();

        return [
            $generus->registration_number,
            $generus->record_number,
            $generus->full_name,
            $generus->school_name,
            $generus->nis,
            $generus->father_name,
            $generus->mother_name,
            $generus->father_occupation,
            $generus->mother_occupation,
            $generus->phone_number,
            $generus->gender,
            $generus->birth_place,
            $generus->birth_date?->format('Y-m-d'),
            $generus->birth_order,
            $generus->sibling_count,
            $generus->school_grade,
            $generus->learning_class,
            $generus->educational_level,
            $generus->status,
            $generus->notes,
            $assignment?->region?->code,
            $assignment?->village?->code,
            $assignment?->group?->code,
            $assignment?->level?->code,
            $assignment?->academicYear?->code,
            $assignment?->status,
            $assignment?->notes,
        ];
    }
}
