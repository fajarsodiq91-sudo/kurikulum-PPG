<?php

namespace App\Support\Sheets;

use App\Models\Communication;
use App\Models\Training;

class CommunicationSheet extends Sheet
{
    public function filename(): string
    {
        return 'komunikasi.xlsx';
    }

    public function headings(): array
    {
        return ['training_title', 'channel', 'subject', 'message', 'status'];
    }

    public function rows(): iterable
    {
        foreach (Communication::with('training')->orderBy('id')->cursor() as $communication) {
            yield [
                $communication->training?->title,
                $communication->channel,
                $communication->subject,
                $communication->message,
                $communication->status,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'training_title' => ['nullable', 'string', 'max:255'],
            'channel' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $trainingId = null;

        if (filled($data['training_title'] ?? null)) {
            $training = Training::where('title', trim($data['training_title']))->first();

            if ($training === null) {
                return 'Pelatihan dengan judul tersebut tidak ditemukan.';
            }

            $trainingId = $training->id;
        }

        Communication::updateOrCreate(
            ['training_id' => $trainingId, 'channel' => trim($data['channel']), 'subject' => trim($data['subject'])],
            ['message' => trim($data['message']), 'status' => trim($data['status'])],
        );

        return null;
    }
}
