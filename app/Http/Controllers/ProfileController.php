<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $oldPhotoPath = $user->photo;
        $newPhotoPath = $this->storePhoto($validated['photo'] ?? null);

        $user->update([
            'name' => $validated['name'],
            'whatsapp' => $validated['whatsapp'] ?? null,
            ...($newPhotoPath !== null ? ['photo' => $newPhotoPath] : []),
        ]);

        if ($newPhotoPath !== null && $oldPhotoPath !== null) {
            Storage::delete($oldPhotoPath);
        }

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Any authenticated viewer may load a user's profile photo - it's shown throughout the
     * app (struktur organisasi, penugasan, dll) wherever that person is referenced.
     */
    public function photo(User $user): StreamedResponse
    {
        abort_if(blank($user->photo) || ! Storage::exists($user->photo), 404);

        return Storage::response($user->photo);
    }

    private function storePhoto(?UploadedFile $photo): ?string
    {
        return $photo?->store(User::PHOTO_DIRECTORY);
    }
}
