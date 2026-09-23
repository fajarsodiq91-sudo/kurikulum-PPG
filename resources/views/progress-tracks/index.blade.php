@extends('layouts.app')

@section('title', 'Progress Tracking')
@section('page-title', 'Progress Tracking')
@section('breadcrumb', 'Kurikulum / Progress Tracking')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Progress Tracking</h2>
        <p class="mt-2 text-sm text-slate-500">Monitor perkembangan generus dalam periode tertentu.</p>
    </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Progress</h2>
                <form method="POST" action="{{ route('progress-tracks.store') }}">
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
                        <label class="block text-sm font-medium mb-2" for="period_label">Periode</label>
                        <input id="period_label" name="period_label" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="overall_status">Status</label>
                        <select id="overall_status" name="overall_status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="good">Baik</option>
                            <option value="average">Cukup</option>
                            <option value="needs_attention">Perlu Perhatian</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="score">Skor</label>
                        <input id="score" name="score" type="number" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="next_goal">Target Selanjutnya</label>
                        <textarea id="next_goal" name="next_goal" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Riwayat Progress</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Generus</th>
                                <th class="py-3 pr-4">Periode</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($progressTracks as $track)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $track->generus?->full_name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $track->period_label }}</td>
                                    <td class="py-3 pr-4">{{ $track->overall_status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada progress tracking.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $progressTracks->links() }}</div>
            </div>
        </div>
    </div>
@endsection
