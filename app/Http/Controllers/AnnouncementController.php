<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('announcements.index', [
            'announcements' => Announcement::with('author')->latest('published_at')->paginate(25),
            'templates' => Announcement::TEMPLATES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAnnouncement($request);
        $imagePath = $this->storeImage($validated['image'] ?? null);

        Announcement::create([
            ...Arr::except($validated, ['image']),
            'image' => $imagePath,
            'user_id' => $request->user()->id,
            'published_at' => $validated['published_at'] ?? ($validated['status'] === 'published' ? now() : null),
        ]);

        return redirect()->route('announcements.index')->with('success', 'Berita berhasil dipublikasikan.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('announcements.edit', [
            'announcement' => $announcement,
            'templates' => Announcement::TEMPLATES,
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $this->validateAnnouncement($request, $announcement);
        $oldImagePath = $announcement->image;
        $newImagePath = $this->storeImage($validated['image'] ?? null);

        $announcement->update([
            ...Arr::except($validated, ['image']),
            ...($newImagePath !== null ? ['image' => $newImagePath] : []),
            'published_at' => $validated['published_at'] ?? ($validated['status'] === 'published' ? ($announcement->published_at ?? now()) : null),
        ]);

        if ($newImagePath !== null && $oldImagePath !== null) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()->route('announcements.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        if ($announcement->image !== null) {
            Storage::disk('public')->delete($announcement->image);
        }

        return redirect()->route('announcements.index')->with('success', 'Berita berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAnnouncement(Request $request, ?Announcement $announcement = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'template' => ['required', Rule::in(array_keys(Announcement::TEMPLATES))],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        return $image?->store(Announcement::IMAGE_DIRECTORY, 'public');
    }
}
