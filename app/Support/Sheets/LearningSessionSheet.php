<?php

namespace App\Support\Sheets;

use App\Models\LearningSession;
use App\Models\User;

/**
 * Export only: sessions are created by the weekly "Generate Jadwal Mingguan" flow (or the
 * single-session form), so a raw import would bypass that national-schedule generation.
 */
class LearningSessionSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'jadwal-kbm.xlsx';
    }

    public function headings(): array
    {
        return ['session_date', 'teacher_name', 'material_title', 'level', 'village_name', 'group_name', 'start_time', 'end_time', 'location', 'status', 'notes'];
    }

    public function rows(): iterable
    {
        foreach (LearningSession::visibleTo($this->user)->with(['teacher', 'material', 'village', 'group'])->orderBy('session_date')->cursor() as $session) {
            yield [
                $session->session_date,
                $session->teacher?->name,
                $session->material?->title,
                $session->level,
                $session->village?->name,
                $session->group?->name,
                $session->start_time,
                $session->end_time,
                $session->location,
                $session->status,
                $session->notes,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        return 'Import tidak didukung untuk Jadwal KBM; gunakan fitur Generate Jadwal Mingguan.';
    }
}
