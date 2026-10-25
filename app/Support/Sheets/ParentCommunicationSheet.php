<?php

namespace App\Support\Sheets;

use App\Models\Generus;
use App\Models\ParentCommunication;
use App\Models\User;
use App\Support\StudentScope;
use Illuminate\Validation\Rule;

class ParentCommunicationSheet extends Sheet
{
    private const PERMISSION = 'manage-parent-communications';

    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'komunikasi-orang-tua.xlsx';
    }

    public function headings(): array
    {
        return ['generus_registration_number', 'guardian_full_name', 'channel', 'communicated_at', 'subject', 'message'];
    }

    public function rows(): iterable
    {
        $ids = StudentScope::viewIds($this->user, self::PERMISSION);

        foreach (StudentScope::limit(ParentCommunication::with(['generus', 'guardian']), $ids, 'generus_id')->orderBy('id')->cursor() as $communication) {
            yield [
                $communication->generus?->registration_number,
                $communication->guardian?->full_name,
                $communication->channel,
                $communication->communicated_at?->format('Y-m-d'),
                $communication->subject,
                $communication->message,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'generus_registration_number' => ['required', 'string', 'max:50'],
            'guardian_full_name' => ['nullable', 'string', 'max:255'],
            'channel' => ['required', Rule::in(ParentCommunication::CHANNELS)],
            'communicated_at' => ['required', 'date'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $generus = Generus::where('registration_number', trim($data['generus_registration_number']))->first();

        if ($generus === null) {
            return 'Generus dengan nomor registrasi tersebut tidak ditemukan.';
        }

        $allowedIds = StudentScope::manageIds($this->user, self::PERMISSION);

        if ($allowedIds !== null && ! in_array($generus->id, $allowedIds, true)) {
            return 'Generus berada di luar wilayah akses Anda.';
        }

        $guardianId = null;

        if (filled($data['guardian_full_name'] ?? null)) {
            $guardian = $generus->guardians()->whereRaw('lower(full_name) = ?', [mb_strtolower(trim($data['guardian_full_name']))])->first();

            if ($guardian === null) {
                return 'Orang tua/wali tersebut bukan wali dari generus yang dimaksud.';
            }

            $guardianId = $guardian->id;
        }

        ParentCommunication::updateOrCreate(
            [
                'generus_id' => $generus->id,
                'channel' => trim($data['channel']),
                'communicated_at' => $data['communicated_at'],
                'subject' => $data['subject'] ?? null,
            ],
            [
                'guardian_id' => $guardianId,
                'teacher_id' => $this->user?->teacher_id,
                'message' => trim($data['message']),
            ],
        );

        return null;
    }
}
