@extends('layouts.app')

@section('title', 'Progress Tracking')
@section('page-title', 'Progress Tracking')
@section('breadcrumb', 'Kurikulum / Progress Tracking')

@section('content')
    @php
        $formShouldOpen = $errors->any();
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Progress Tracking</h2>
            <p class="mt-2 text-sm text-slate-500">Monitor perkembangan generus dalam periode tertentu.</p>
        </div>
        @if (\App\Support\Access::can('manage-progress-tracking'))
            <button type="button" id="open-create-progress-track" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                + Tambah Progress
            </button>
        @endif
    </div>

        @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('progress-tracks.export'), 'importUrl' => \App\Support\Access::can('manage-progress-tracking') ? route('progress-tracks.import') : null])

        <x-filter-bar :action="route('progress-tracks.index')" search-placeholder="Cari nama generus...">
            <x-filter-select name="academic_year_id" label="Tahun" :options="$academicYears" />
            <x-filter-select name="semester_id" label="Semester" :options="$semesters" />
            <x-filter-select name="overall_status" label="Status" :options="['good' => 'Baik', 'average' => 'Cukup', 'needs_attention' => 'Perlu Perhatian']" />
        </x-filter-bar>

        @if (\App\Support\Access::can('manage-progress-tracking'))
            <div id="create-progress-track-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $formShouldOpen ? '' : 'hidden' }}" data-create-progress-track-close></div>

            <div id="create-progress-track-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $formShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-progress-track-title">
                <div class="mx-auto my-6 w-full max-w-lg">
                    <x-card class="relative">
                        <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-progress-track-close>&times;</button>
                        <h2 id="create-progress-track-title" class="mb-4 text-xl font-semibold">Tambah Progress</h2>
                        <form method="POST" action="{{ route('progress-tracks.store') }}">
                            @csrf
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
                                <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="">-- Pilih --</option>
                                    @foreach($generus as $item)
                                        <option value="{{ $item->id }}" @selected(old('generus_id') == $item->id)>{{ $item->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun</label>
                                <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="">-- Pilih --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="semester_id">Semester</label>
                                <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="">-- Pilih --</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}" @selected(old('semester_id') == $semester->id)>{{ $semester->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="period_label">Periode</label>
                                <input id="period_label" name="period_label" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('period_label') }}">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="overall_status">Status</label>
                                <select id="overall_status" name="overall_status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="good" @selected(old('overall_status', 'good') === 'good')>Baik</option>
                                    <option value="average" @selected(old('overall_status') === 'average')>Cukup</option>
                                    <option value="needs_attention" @selected(old('overall_status') === 'needs_attention')>Perlu Perhatian</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="score">Skor</label>
                                <input id="score" name="score" type="number" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('score') }}">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                                <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="next_goal">Target Selanjutnya</label>
                                <textarea id="next_goal" name="next_goal" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('next_goal') }}</textarea>
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                        </form>
                    </x-card>
                </div>
            </div>
        @endif

        <div class="grid gap-6">
            <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
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

    <script>
        (function () {
            const openButton = document.getElementById('open-create-progress-track');
            const backdrop = document.getElementById('create-progress-track-backdrop');
            const modal = document.getElementById('create-progress-track-modal');

            if (!openButton || !backdrop || !modal) {
                return;
            }

            function openModal() {
                backdrop.classList.remove('hidden');
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                backdrop.classList.add('hidden');
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            openButton.addEventListener('click', openModal);
            document.querySelectorAll('[data-create-progress-track-close]').forEach(function (el) {
                el.addEventListener('click', closeModal);
            });
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
