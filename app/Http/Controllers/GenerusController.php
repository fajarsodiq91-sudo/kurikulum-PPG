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
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GenerusController extends Controller
{
    public function index(): View
    {
        return view('generus.index', [
            'generus' => Generus::with('assignments')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
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

        DB::transaction(function () use ($validated): void {
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
        });

        return redirect()->route('generus.index')->with('success', 'Generus berhasil ditambahkan.');
    }

    public function create(): View
    {
        return view('generus.create', [
            'generatedRegistrationNumber' => $this->generateRegistrationNumber(),
            'generatedRecordNumber' => $this->generateRecordNumber(),
            'regions' => Region::where('is_active', true)->get(),
            'villages' => Village::where('is_active', true)->get(),
            'groups' => Group::where('is_active', true)->get(),
            'levels' => Level::where('is_active', true)->orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::where('is_active', true)->get(),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ]);

        Excel::import(new GenerusImport, $validated['file']);

        return redirect()->route('generus.index')->with('success', 'Data generus berhasil diimpor dari XLSX.');
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new GenerusExport, 'generus.xlsx');
    }

    private function generateRegistrationNumber(): string
    {
        $prefix = now()->format('ym');
        $lastNumber = Generus::query()
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
        $lastNumber = Generus::query()
            ->whereNotNull('record_number')
            ->orderByDesc('record_number')
            ->value('record_number');

        $sequence = $lastNumber !== null && ctype_digit($lastNumber)
            ? (int) $lastNumber + 1
            : 1;

        return str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
