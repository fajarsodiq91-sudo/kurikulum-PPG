<?php

namespace App\Support\Sheets;

use App\Models\Generus;
use App\Models\LearningSession;
use App\Models\SessionAttendance;
use App\Models\User;
use App\Support\StudentScope;

class SessionAttendanceSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'absensi-sesi.xlsx';
    }

    public function headings(): array
    {
        return ['session_date', 'material_title', 'level', 'village_name', 'group_name', 'generus_registration_number', 'status', 'notes'];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::ids($this->user, 'manage-learning-attendance');

        foreach (StudentScope::limit(SessionAttendance::with(['learningSession.material', 'learningSession.village', 'learningSession.group', 'generus']), $ids, 'generus_id')->orderBy('id')->cursor() as $attendance) {
            $session = $attendance->learningSession;

            yield [
                $session?->session_date,
                $session?->material?->title,
                $session?->level,
                $session?->village?->name,
                $session?->group?->name,
                $attendance->generus?->registration_number,
                $attendance->status,
                $attendance->notes,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'session_date' => ['required', 'date'],
            'material_title' => ['required', 'string', 'max:255'],
            'level' => ['required', 'string', 'max:20'],
            'village_name' => ['nullable', 'string', 'max:255'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'generus_registration_number' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $session = LearningSession::whereDate('session_date', $data['session_date'])
            ->whereHas('material', fn ($query) => $query->where('title', trim($data['material_title'])))
            ->where('level', trim($data['level']))
            ->when(filled($data['village_name'] ?? null), fn ($query) => $query->whereHas('village', fn ($v) => $v->where('name', trim($data['village_name']))))
            ->when(filled($data['group_name'] ?? null), fn ($query) => $query->whereHas('group', fn ($g) => $g->where('name', trim($data['group_name']))))
            ->first();

        if ($session === null) {
            return 'Sesi KBM tidak ditemukan untuk tanggal, materi, tingkat, dan desa/kelompok tersebut.';
        }

        if (! $session->isAttendableBy($this->user)) {
            return 'Sesi KBM berada di luar wilayah akses Anda.';
        }

        $generus = Generus::where('registration_number', trim($data['generus_registration_number']))->first();

        if ($generus === null) {
            return 'Generus dengan nomor registrasi tersebut tidak ditemukan.';
        }

        $allowedIds = StudentScope::ids($this->user, 'manage-learning-attendance');

        if ($allowedIds !== null && ! in_array($generus->id, $allowedIds, true)) {
            return 'Generus berada di luar wilayah akses Anda.';
        }

        SessionAttendance::updateOrCreate(
            ['learning_session_id' => $session->id, 'generus_id' => $generus->id],
            ['status' => trim($data['status']), 'method' => SessionAttendance::METHOD_MANUAL, 'notes' => $data['notes'] ?? null],
        );

        return null;
    }
}
