<?php

namespace App\Http\Controllers;

use App\Exports\GenerusExport;
use App\Imports\GenerusImport;
use App\Models\AcademicYear;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\Level;
use App\Models\Region;
use App\Models\User;
use App\Models\Village;
use chillerlan\QRCode\QRCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class GenerusController extends Controller
{
    /**
     * Biodata fields an edit may change; registration, record number and NIS stay fixed.
     *
     * @var list<string>
     */
    private const EDITABLE_FIELDS = [
        'full_name',
        'school_name',
        'father_name',
        'mother_name',
        'father_occupation',
        'mother_occupation',
        'phone_number',
        'gender',
        'birth_place',
        'birth_date',
        'birth_order',
        'sibling_count',
        'school_grade',
        'learning_class',
        'educational_level',
        'status',
        'notes',
    ];

    /**
     * @var list<string>
     */
    private const PLACEMENT_FIELDS = ['region_id', 'village_id', 'group_id', 'level_id', 'academic_year_id'];

    public function index(Request $request): View
    {
        return view('generus.index', [
            'generus' => Generus::visibleTo($request->user())
                ->with([
                    'assignments' => fn ($query) => $query->where('status', '!=', 'ended'),
                    'assignments.region',
                    'assignments.village',
                    'assignments.group',
                ])
                ->latest()
                ->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateGenerus($request);

        $this->ensurePlacementIsWithinUserScope($request->user(), $validated);

        $photoPath = $this->storePhoto($validated['photo'] ?? null);

        retry(3, fn () => DB::transaction(function () use ($validated, $photoPath): void {
            $registrationNumber = $this->generateRegistrationNumber();
            $recordNumber = $this->generateRecordNumber();

            $generus = Generus::create([
                'registration_number' => $registrationNumber,
                'record_number' => $recordNumber,
                'full_name' => $validated['full_name'],
                'nis' => $registrationNumber,
                'school_name' => $validated['school_name'] ?? null,
                'father_name' => $validated['father_name'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'father_occupation' => $validated['father_occupation'] ?? null,
                'mother_occupation' => $validated['mother_occupation'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'birth_place' => $validated['birth_place'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'birth_order' => $validated['birth_order'] ?? null,
                'sibling_count' => $validated['sibling_count'] ?? null,
                'school_grade' => $validated['school_grade'] ?? null,
                'learning_class' => $validated['learning_class'] ?? null,
                'educational_level' => $validated['educational_level'] ?? null,
                'photo' => $photoPath,
                'status' => $validated['status'],
                'transfer_destination' => $validated['transfer_destination'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            GenerusAssignment::create([
                'generus_id' => $generus->id,
                'region_id' => $validated['region_id'] ?? null,
                'village_id' => $validated['village_id'] ?? null,
                'group_id' => $validated['group_id'] ?? null,
                'level_id' => $validated['level_id'] ?? null,
                'academic_year_id' => $validated['academic_year_id'] ?? null,
                'status' => $validated['assignment_status'],
                'assigned_at' => now()->toDateString(),
                'notes' => ($validated['transfer_destination'] ?? null) === 'external'
                    ? 'Pindah sambung ke luar daerah.'
                    : ($validated['notes'] ?? null),
            ]);
        }), when: fn (Throwable $exception): bool => $exception instanceof UniqueConstraintViolationException);

        return redirect()->route('generus.index')->with('success', 'Generus berhasil ditambahkan.');
    }

    public function create(Request $request): View
    {
        return view('generus.create', [
            'generatedRegistrationNumber' => $this->generateRegistrationNumber(),
            'generatedRecordNumber' => $this->generateRecordNumber(),
            ...$this->placementOptions($request->user()),
        ]);
    }

    public function show(Request $request, Generus $generus): View
    {
        $this->ensureGenerusIsVisible($request->user(), $generus);

        $generus->load([
            'assignments' => fn ($query) => $query->latest('assigned_at')->latest('id'),
            'assignments.region',
            'assignments.village',
            'assignments.group',
            'assignments.level',
            'assignments.academicYear',
        ]);

        return view('generus.show', ['generus' => $generus]);
    }

    public function idCard(Request $request, Generus $generus): View
    {
        $this->ensureGenerusIsVisible($request->user(), $generus);

        return view('id-cards.show', [
            'cardTitle' => 'Kartu Identitas Generus',
            'name' => $generus->full_name,
            'registrationNumber' => $generus->registration_number,
            'photoDataUri' => $generus->photoDataUri(),
            'qrCode' => (new QRCode)->render($generus->registration_number),
            'backUrl' => route('generus.show', $generus),
        ]);
    }

    public function edit(Request $request, Generus $generus): View
    {
        $this->ensureGenerusIsVisible($request->user(), $generus);

        return view('generus.edit', [
            'generus' => $generus,
            'currentAssignment' => $this->currentAssignment($generus),
            ...$this->placementOptions($request->user()),
        ]);
    }

    public function update(Request $request, Generus $generus): RedirectResponse
    {
        $user = $request->user();
        $this->ensureGenerusIsVisible($user, $generus);

        $validated = $this->validateGenerus($request);
        $this->ensurePlacementIsWithinUserScope($user, $validated);

        $oldPhotoPath = $generus->photo;
        $newPhotoPath = $this->storePhoto($validated['photo'] ?? null);

        DB::transaction(function () use ($generus, $validated, $newPhotoPath): void {
            $generus->update([
                ...collect(self::EDITABLE_FIELDS)->mapWithKeys(fn (string $field): array => [$field => $validated[$field] ?? null])->all(),
                'transfer_destination' => $validated['transfer_destination'] ?? null,
                ...($newPhotoPath !== null ? ['photo' => $newPhotoPath] : []),
            ]);

            $this->syncPlacement($generus, $validated);
        });

        if ($newPhotoPath !== null && $oldPhotoPath !== null) {
            Storage::delete($oldPhotoPath);
        }

        return redirect()->route('generus.show', $generus)->with('success', 'Data generus berhasil diperbarui.');
    }

    public function destroy(Request $request, Generus $generus): RedirectResponse
    {
        $this->ensureGenerusIsVisible($request->user(), $generus);

        $generus->delete();

        return redirect()->route('generus.index')->with('success', "Generus {$generus->full_name} berhasil dihapus.");
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $import = new GenerusImport($request->user());

            Excel::import($import, $validated['file']);

            if ($import->errors() !== []) {
                throw ValidationException::withMessages($import->errors());
            }
        });

        return redirect()->route('generus.index')->with('success', 'Data generus berhasil diimpor dari XLSX.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(new GenerusExport($request->user()), 'generus.xlsx');
    }

    /**
     * @param  array<string, mixed>  $validated
     *
     * @throws ValidationException
     */
    private function ensurePlacementIsWithinUserScope(User $user, array $validated): void
    {
        if ($user->hasGlobalAccess(Generus::MANAGE_PERMISSION)) {
            return;
        }

        if (($validated['transfer_destination'] ?? null) === 'external') {
            throw ValidationException::withMessages([
                'transfer_destination' => 'Hanya admin dengan akses global yang dapat mencatat pindah sambung ke luar daerah.',
            ]);
        }

        $isCovered = $user->coversPlacement(
            Generus::MANAGE_PERMISSION,
            (int) $validated['region_id'],
            (int) $validated['village_id'],
            (int) $validated['group_id'],
        );

        if (! $isCovered) {
            throw ValidationException::withMessages([
                'group_id' => 'Kelompok yang dipilih berada di luar wilayah akses Anda.',
            ]);
        }
    }

    /**
     * @param  Builder<Group>  $query
     */
    private function limitGroupsToScope(Builder $query, User $user): void
    {
        $placementIds = $user->scopedPlacementIds(Generus::MANAGE_PERMISSION);

        if ($placementIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $scoped) use ($placementIds): void {
            if (isset($placementIds['group_id'])) {
                $scoped->orWhereIn('id', $placementIds['group_id']);
            }

            if (isset($placementIds['village_id'])) {
                $scoped->orWhereIn('village_id', $placementIds['village_id']);
            }

            if (isset($placementIds['region_id'])) {
                $scoped->orWhereHas('village', fn (Builder $village) => $village->whereIn('region_id', $placementIds['region_id']));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function validateGenerus(Request $request): array
    {
        if ($request->input('status') !== 'pindah_sambung') {
            $request->merge(['transfer_destination' => null]);
        }

        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'max:50'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'birth_order' => ['nullable', 'integer', 'min:1', 'max:32767'],
            'sibling_count' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'school_grade' => ['nullable', 'string', 'max:50'],
            'learning_class' => ['nullable', 'string', 'max:100'],
            'educational_level' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'pindah_sambung', 'married'])],
            'transfer_destination' => [
                Rule::requiredIf(fn (): bool => $request->input('status') === 'pindah_sambung'),
                'nullable',
                Rule::in(['internal', 'external']),
            ],
            'region_id' => [
                Rule::requiredIf(fn (): bool => $request->input('transfer_destination') !== 'external'),
                'nullable',
                Rule::exists('regions', 'id')->where('is_active', true),
            ],
            'village_id' => [
                Rule::requiredIf(fn (): bool => $request->input('transfer_destination') !== 'external'),
                'nullable',
                Rule::exists('villages', 'id')
                    ->where('region_id', $request->input('region_id'))
                    ->where('is_active', true),
            ],
            'group_id' => [
                Rule::requiredIf(fn (): bool => $request->input('transfer_destination') !== 'external'),
                'nullable',
                Rule::exists('groups', 'id')
                    ->where('village_id', $request->input('village_id'))
                    ->where('is_active', true),
            ],
            'level_id' => [
                Rule::requiredIf(fn (): bool => $request->input('transfer_destination') !== 'external'),
                'nullable',
                Rule::exists('levels', 'id')->where('is_active', true),
            ],
            'academic_year_id' => [
                Rule::requiredIf(fn (): bool => $request->input('transfer_destination') !== 'external'),
                'nullable',
                Rule::exists('academic_years', 'id')->where('is_active', true),
            ],
            'assignment_status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function storePhoto(?UploadedFile $photo): ?string
    {
        return $photo?->store(Generus::PHOTO_DIRECTORY);
    }

    /**
     * Ends the current placement and records a new one when the placement changed;
     * otherwise only the current placement's status is updated.
     *
     * @param  array<string, mixed>  $validated
     */
    private function syncPlacement(Generus $generus, array $validated): void
    {
        $isExternal = ($validated['transfer_destination'] ?? null) === 'external';
        $placement = collect(self::PLACEMENT_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $isExternal ? null : (int) $validated[$field]])
            ->all();

        $currentAssignment = $this->currentAssignment($generus);

        $isSamePlacement = $currentAssignment !== null && collect($placement)
            ->every(fn (?int $id, string $field): bool => ($currentAssignment->{$field} === null ? null : (int) $currentAssignment->{$field}) === $id);

        if ($isSamePlacement) {
            $currentAssignment->update(['status' => $validated['assignment_status']]);

            return;
        }

        $currentAssignment?->update([
            'status' => 'ended',
            'ended_at' => now()->toDateString(),
        ]);

        $generus->assignments()->create([
            ...$placement,
            'status' => $validated['assignment_status'],
            'assigned_at' => now()->toDateString(),
            'notes' => $isExternal ? 'Pindah sambung ke luar daerah.' : null,
        ]);
    }

    private function currentAssignment(Generus $generus): ?GenerusAssignment
    {
        return $generus->assignments()
            ->where('status', '!=', 'ended')
            ->latest('assigned_at')
            ->latest('id')
            ->first();
    }

    /**
     * Placement dropdown options limited to the user's scope.
     *
     * @return array<string, mixed>
     */
    private function placementOptions(User $user): array
    {
        $isGlobal = $user->hasGlobalAccess(Generus::MANAGE_PERMISSION);

        $groups = Group::where('is_active', true)
            ->when(! $isGlobal, fn (Builder $query) => $this->limitGroupsToScope($query, $user))
            ->get();
        $villages = Village::where('is_active', true)
            ->when(! $isGlobal, fn (Builder $query) => $query->whereIn('id', $groups->pluck('village_id')))
            ->get();
        $regions = Region::where('is_active', true)
            ->when(! $isGlobal, fn (Builder $query) => $query->whereIn('id', $villages->pluck('region_id')))
            ->get();

        return [
            'regions' => $regions,
            'villages' => $villages,
            'groups' => $groups,
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
        ];
    }

    /**
     * Out-of-scope generus respond with 404 so their existence is not revealed.
     */
    private function ensureGenerusIsVisible(User $user, Generus $generus): void
    {
        abort_unless(Generus::visibleTo($user)->whereKey($generus->id)->exists(), 404);
    }

    private function generateRegistrationNumber(): string
    {
        $prefix = now()->format('ym');
        $lastNumber = Generus::withTrashed()
            ->where('registration_number', 'like', $prefix.'%')
            ->orderByDesc('registration_number')
            ->value('registration_number');

        $sequence = $lastNumber !== null && preg_match('/^'.preg_quote($prefix, '/').'(\d{4})$/', $lastNumber, $matches)
            ? (int) $matches[1] + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function generateRecordNumber(): string
    {
        $lastNumber = Generus::withTrashed()
            ->whereNotNull('record_number')
            ->orderByDesc('record_number')
            ->value('record_number');

        $sequence = $lastNumber !== null && ctype_digit($lastNumber)
            ? (int) $lastNumber + 1
            : 1;

        return str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
