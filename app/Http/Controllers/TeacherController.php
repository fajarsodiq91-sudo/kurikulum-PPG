<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\Group;
use App\Models\Region;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Village;
use App\Support\Sheets\TeacherSheet;
use chillerlan\QRCode\QRCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class TeacherController extends Controller
{
    use HandlesSheets;

    private const MANAGE_PERMISSION = 'manage-teachers';

    public function index(Request $request): View
    {
        return view('teachers.index', [
            'teachers' => Teacher::latest()->paginate(25),
            ...$this->placementOptions($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTeacher($request);
        $photoPath = $this->storePhoto($validated['photo'] ?? null);

        retry(3, fn () => Teacher::create([
            ...Arr::except($validated, 'photo'),
            'registration_number' => Teacher::nextRegistrationNumber(),
            'photo' => $photoPath,
        ]), when: fn (Throwable $exception): bool => $exception instanceof UniqueConstraintViolationException);

        return redirect()->route('teachers.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return $this->exportSheet(new TeacherSheet($request->user()));
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new TeacherSheet($request->user()));
    }

    public function edit(Request $request, Teacher $teacher): View
    {
        return view('teachers.edit', [
            'teacher' => $teacher,
            ...$this->placementOptions($request->user()),
        ]);
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
        $user = $request->user();
        $request->merge([...$this->lockedPlacement($user), 'region_id' => Region::karawangTimur()->id]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('teachers', 'email')->ignore($teacher?->id)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'region_id' => ['required', Rule::exists('regions', 'id')->where('is_active', true)],
            'village_id' => [
                'required',
                Rule::exists('villages', 'id')->where('region_id', $request->input('region_id'))->where('is_active', true),
            ],
            'group_id' => [
                'required',
                Rule::exists('groups', 'id')->where('village_id', $request->input('village_id'))->where('is_active', true),
            ],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $user->coversPlacement(self::MANAGE_PERMISSION, (int) $validated['region_id'], (int) $validated['village_id'], (int) $validated['group_id'])) {
            throw ValidationException::withMessages([
                'group_id' => 'Kelompok yang dipilih berada di luar wilayah akses Anda.',
            ]);
        }

        return $validated;
    }

    /**
     * Placement values a scoped user cannot change: a group role fixes village and group;
     * a village role fixes the village.
     *
     * @return array<string, int>
     */
    private function lockedPlacement(?User $user): array
    {
        if ($user === null || $user->hasGlobalAccess(self::MANAGE_PERMISSION)) {
            return [];
        }

        $ids = $user->scopedPlacementIds(self::MANAGE_PERMISSION);

        if (count($ids['group_id'] ?? []) === 1 && ! isset($ids['village_id']) && ! isset($ids['region_id'])) {
            $group = Group::find($ids['group_id'][0]);

            return $group === null ? [] : [
                'village_id' => $group->village_id,
                'group_id' => $group->id,
            ];
        }

        if (count($ids['village_id'] ?? []) === 1 && ! isset($ids['region_id'])) {
            $village = Village::find($ids['village_id'][0]);

            return $village === null ? [] : ['village_id' => $village->id];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function placementOptions(?User $user): array
    {
        if ($user === null) {
            return ['villages' => collect(), 'groups' => collect(), 'lockedPlacement' => []];
        }

        $isGlobal = $user->hasGlobalAccess(self::MANAGE_PERMISSION);
        $ids = $user->scopedPlacementIds(self::MANAGE_PERMISSION);

        $groups = Group::where('is_active', true)
            ->when(! $isGlobal, function (Builder $query) use ($ids): void {
                if ($ids === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function (Builder $scoped) use ($ids): void {
                    if (isset($ids['group_id'])) {
                        $scoped->orWhereIn('id', $ids['group_id']);
                    }

                    if (isset($ids['village_id'])) {
                        $scoped->orWhereIn('village_id', $ids['village_id']);
                    }

                    if (isset($ids['region_id'])) {
                        $scoped->orWhereHas('village', fn (Builder $village) => $village->whereIn('region_id', $ids['region_id']));
                    }
                });
            })
            ->orderBy('name')
            ->get();
        $villages = Village::where('is_active', true)
            ->when(! $isGlobal, fn (Builder $query) => $query->whereIn('id', $groups->pluck('village_id')))
            ->orderBy('name')
            ->get();

        return [
            'villages' => $villages,
            'groups' => $groups,
            'lockedPlacement' => $this->lockedPlacement($user),
        ];
    }

    private function storePhoto(?UploadedFile $photo): ?string
    {
        return $photo?->store(Teacher::PHOTO_DIRECTORY);
    }
}
