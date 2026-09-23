@extends('layouts.app')

@section('title', 'Rapor Generus')
@section('page-title', 'Rapor Generus')
@section('breadcrumb', 'Rapor / Rapor Generus')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Rapor</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Rapor Generus</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola penilaian akhir dan predikat generus.</p>
    </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Rapor</h2>
                <form method="POST" action="{{ route('report-cards.store') }}">
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
                        <label class="block text-sm font-medium mb-2" for="final_score">Nilai Akhir</label>
                        <input id="final_score" name="final_score" type="number" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="predicate">Predikat</label>
                        <input id="predicate" name="predicate" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="recommendation">Rekomendasi</label>
                        <textarea id="recommendation" name="recommendation" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="remarks">Catatan</label>
                        <textarea id="remarks" name="remarks" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Rapor</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Generus</th>
                                <th class="py-3 pr-4">Nilai</th>
                                <th class="py-3 pr-4">Predikat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportCards as $card)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $card->generus?->full_name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $card->final_score ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $card->predicate ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada rapor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $reportCards->links() }}</div>
            </div>
        </div>
    </div>
@endsection
