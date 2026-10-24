@extends('layouts.app')

@section('title', 'Audit Tahunan')
@section('page-title', 'Audit Tahunan')
@section('breadcrumb', 'Audit Tahunan')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Audit Tahunan</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Audit Tahunan</h2>
        <p class="mt-2 text-sm text-slate-500">Ringkasan evaluasi perkembangan generus per tahun dan semester.</p>
    </div>

    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('annual-audits.export'), 'importUrl' => \App\Support\Access::can('manage-annual-audit') ? route('annual-audits.import') : null])

    <div class="grid gap-6 lg:grid-cols-3">
        @if (\App\Support\Access::can('manage-annual-audit'))
            <x-card class="lg:col-span-1">
                <h2 class="mb-4 text-xl font-semibold">Tambah Audit</h2>
                <form method="POST" action="{{ route('annual-audits.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
                        <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($generus as $item)
                                <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun</label>
                        <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="semester_id">Semester</label>
                        <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="audit_type">Tipe Audit</label>
                        <select id="audit_type" name="audit_type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="annual-review">Review Tahunan</option>
                            <option value="mid-year-review">Review Tengah Tahun</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="overall_status">Status Umum</label>
                        <select id="overall_status" name="overall_status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="good">Baik</option>
                            <option value="average">Cukup</option>
                            <option value="needs_attention">Perlu Perhatian</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="summary">Ringkasan</label>
                        <textarea id="summary" name="summary" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="recommendation">Rekomendasi</label>
                        <textarea id="recommendation" name="recommendation" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </form>
            </x-card>
        @endif

        <x-card class="lg:col-span-2">
            <h2 class="mb-4 text-xl font-semibold">Riwayat Audit</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Generus</th>
                            <th class="py-3 pr-4">Tipe</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($annualAudits as $audit)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">{{ $audit->generus?->full_name ?? '-' }}</td>
                                <td class="py-3 pr-4">{{ $audit->audit_type }}</td>
                                <td class="py-3 pr-4">{{ $audit->overall_status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6"><x-empty-state title="Belum ada audit tahunan." class="border-none bg-transparent" /></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $annualAudits->links() }}</div>
        </x-card>
    </div>
@endsection
