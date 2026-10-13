<?php

namespace App\Support\Sheets;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * The announcement's image is a file upload and stays a manual step in the UI; import only
 * manages title, body, template, status, and publish date, keyed by title.
 */
class AnnouncementSheet extends Sheet
{
    public function __construct(private ?User $user = null) {}

    public function filename(): string
    {
        return 'berita.xlsx';
    }

    public function headings(): array
    {
        return ['title', 'body', 'template', 'status', 'published_at', 'author_email'];
    }

    public function rows(): iterable
    {
        foreach (Announcement::with('author')->orderBy('id')->cursor() as $announcement) {
            yield [
                $announcement->title,
                $announcement->body,
                $announcement->template,
                $announcement->status,
                $announcement->published_at?->format('Y-m-d H:i:s'),
                $announcement->author?->email,
            ];
        }
    }

    public function importRow(array $row): ?string
    {
        [$data, $error] = $this->validated($row, [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'template' => ['required', Rule::in(array_keys(Announcement::TEMPLATES))],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ]);

        if ($error !== null) {
            return $error;
        }

        $existing = Announcement::where('title', trim($data['title']))->first();

        $publishedAt = $data['published_at'] ?? ($data['status'] === 'published' ? ($existing?->published_at ?? now()) : null);

        Announcement::updateOrCreate(
            ['title' => trim($data['title'])],
            [
                'body' => trim($data['body']),
                'template' => $data['template'],
                'status' => $data['status'],
                'published_at' => $publishedAt,
                'user_id' => $existing?->user_id ?? $this->user?->id,
            ],
        );

        return null;
    }
}
