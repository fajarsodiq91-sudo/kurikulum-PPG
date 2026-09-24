<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use chillerlan\QRCode\QRCode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class TeacherController extends Controller
{
    public function index(): View
    {
        return view('teachers.index', [
            'teachers' => Teacher::latest()->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTeacher($request);
        $photoPath = $this->storePhoto($validated['photo'] ?? null);

        retry(3, fn () => Teacher::create([
            ...Arr::except($validated, 'photo'),
            'registration_number' => $this->generateRegistrationNumber(),
            'photo' => $photoPath,
        ]), when: fn (Throwable $exception): bool => $exception instanceof UniqueConstraintViolationException);

        return redirect()->route('teachers.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function edit(Teacher $teacher): View
    {
        return view('teachers.edit', ['teacher' => $teacher]);
    }

    public function update(Request $request, Teacher $teacher): RedirectResponse
    {
        $validated = $this->validateTeacher($request, $teacher);
        $oldPhotoPath = $teacher->photo;
        $newPhotoPath = $this->storePhoto($validated['photo'] ?? null);

        $teacher->update([
            ...Arr::except($validated, 'photo'),
            ...($newPhotoPath !== null ? ['photo' => $newPhotoPath] : []),
        ]);

        if ($newPhotoPath !== null && $oldPhotoPath !== null) {
            Storage::delete($oldPhotoPath);
        }

        return redirect()->route('teachers.index')->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        if ($teacher->hasActivityHistory()) {
            return back()->withErrors([
                'teacher' => 'Guru ini masih tercatat di sesi KBM, evaluasi, tindak lanjut, atau penugasan sehingga tidak dapat dihapus. Ubah statusnya menjadi Nonaktif.',
            ]);
        }

        $teacher->delete();

        if ($teacher->photo !== null) {
            Storage::delete($teacher->photo);
        }

        return redirect()->route('teachers.index')->with('success', 'Data guru berhasil dihapus.');
    }

    public function idCard(Teacher $teacher): View
    {
        return view('id-cards.show', [
            'cardTitle' => 'Kartu Identitas Guru',
            'name' => $teacher->name,
            'registrationNumber' => $teacher->registration_number,
            'photoDataUri' => $teacher->photoDataUri(),
            'qrCode' => (new QRCode)->render($teacher->registration_number),
            'backUrl' => route('teachers.edit', $teacher),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTeacher(Request $request, ?Teacher $teacher = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('teachers', 'email')->ignore($teacher?->id)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function storePhoto(?UploadedFile $photo): ?string
    {
        return $photo?->store(Teacher::PHOTO_DIRECTORY);
    }

    /**
     * Teacher numbers follow the generus format: YYMM followed by a 4-digit monthly sequence.
     */
    private function generateRegistrationNumber(): string
    {
        $prefix = now()->format('ym');
        $lastNumber = Teacher::where('registration_number', 'like', $prefix.'%')
            ->orderByDesc('registration_number')
            ->value('registration_number');

        $sequence = $lastNumber !== null && preg_match('/^'.preg_quote($prefix, '/').'(\d{4})$/', $lastNumber, $matches)
            ? (int) $matches[1] + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
