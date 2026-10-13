<?php

namespace App\Support\Sheets;

use App\Models\FollowUp;
use App\Models\Generus;
use App\Models\Teacher;
use App\Models\User;
use App\Support\StudentScope;

class FollowUpSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'follow-up.xlsx';
    }

    public function headings(): array
    {
        return ['generus_registration_number', 'teacher_registration_number', 'title', 'priority', 'follow_up_date', 'status', 'notes', 'next_action'];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::ids($this->user, 'manage-follow-ups');

        foreach (StudentScope::limit(FollowUp::with(['generus', 'teacher']), $ids, 'generus_id')->orderBy('id')->cursor() as $followUp) {
            yield [
                $followUp->generus?->registration_number,
                $followUp->teacher?->registration_number,
                $followUp->title,
                $followUp->priority,
                $followUp->follow_up_date,
                $followUp->status,
                $followUp->notes,
                $followUp->next_action,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'generus_registration_number' => ['required', 'string', 'max:50'],
            'teacher_registration_number' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:20'],
            'follow_up_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'next_action' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $generus = Generus::where('registration_number', trim($data['generus_registration_number']))->first();

        if ($generus === null) {
            return 'Generus dengan nomor registrasi tersebut tidak ditemukan.';
        }

        $allowedIds = StudentScope::ids($this->user, 'manage-follow-ups');

        if ($allowedIds !== null && ! in_array($generus->id, $allowedIds, true)) {
            return 'Generus berada di luar wilayah akses Anda.';
        }

        // A teacher restricted to their own students may only log follow-ups under their own name.
        $teacher = $this->user?->teacher_id !== null && $allowedIds !== null
            ? $this->user->teacher
            : (filled($data['teacher_registration_number'] ?? null)
                ? Teacher::where('registration_number', trim($data['teacher_registration_number']))->first()
                : null);

        if ($teacher === null) {
            return 'Guru tidak ditemukan.';
        }

        FollowUp::updateOrCreate(
            [
                'generus_id' => $generus->id,
                'teacher_id' => $teacher->id,
                'title' => trim($data['title']),
                'follow_up_date' => $data['follow_up_date'],
            ],
            [
                'priority' => trim($data['priority']),
                'status' => trim($data['status']),
                'notes' => $data['notes'] ?? null,
                'next_action' => $data['next_action'] ?? null,
            ],
        );

        return null;
    }
}
