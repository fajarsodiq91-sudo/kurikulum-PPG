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

        @if (\App\Support\Access::can('manage-progress-tracking'))
            <div id="edit-progress-track-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50" data-edit-progress-track-close></div>

            <div id="edit-progress-track-modal" class="fixed inset-0 z-50 hidden overflow-y-auto p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="edit-progress-track-title">
                <div class="mx-auto my-6 w-full max-w-lg">
                    <x-card class="relative">
                        <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-edit-progress-track-close>&times;</button>
                        <h2 id="edit-progress-track-title" class="mb-4 text-xl font-semibold">Ubah Progress</h2>
                        <form method="POST" id="edit-progress-track-form">
                            @csrf
                            @method('PUT')
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="edit_generus_id">Generus</label>
                                <select id="edit_generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    @foreach($generus as $item)
                                        <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="edit_academic_year_id">Tahun</label>
                                <select id="edit_academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="edit_semester_id">Semester</label>
                                <select id="edit_semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="edit_period_label">Periode</label>
                                <input id="edit_period_label" name="period_label" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="edit_overall_status">Status</label>
                                <select id="edit_overall_status" name="overall_status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <option value="good">Baik</option>
                                    <option value="average">Cukup</option>
                                    <option value="needs_attention">Perlu Perhatian</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="edit_score">Skor</label>
                                <input id="edit_score" name="score" type="number" step="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="edit_notes">Catatan</label>
                                <textarea id="edit_notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2" for="edit_next_goal">Target Selanjutnya</label>
                                <textarea id="edit_next_goal" name="next_goal" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan Perubahan</button>
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
                                @if (\App\Support\Access::can('manage-progress-tracking'))
                                    <th class="py-3 pr-4 text-right">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($progressTracks as $track)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $track->generus?->full_name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $track->period_label }}</td>
                                    <td class="py-3 pr-4">{{ $track->overall_status }}</td>
                                    @if (\App\Support\Access::can('manage-progress-tracking'))
                                        <td class="py-3 pr-4 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <x-action-icon
                                                    type="button"
                                                    label="Ubah"
                                                    color="amber"
                                                    data-edit-progress-track
                                                    data-action="{{ route('progress-tracks.update', $track) }}"
                                                    data-generus-id="{{ $track->generus_id }}"
                                                    data-academic-year-id="{{ $track->academic_year_id }}"
                                                    data-semester-id="{{ $track->semester_id }}"
                                                    data-period-label="{{ $track->period_label }}"
                                                    data-overall-status="{{ $track->overall_status }}"
                                                    data-score="{{ $track->score }}"
                                                    data-notes="{{ $track->notes }}"
                                                    data-next-goal="{{ $track->next_goal }}"
                                                ><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>
                                                <form method="POST" action="{{ route('progress-tracks.destroy', $track) }}" onsubmit="return confirm(@js('Hapus progress '.$track->period_label.'? Tindakan ini tidak dapat dibatalkan.'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-action-icon label="Hapus" color="rose" type="submit"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></x-action-icon>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-500">Belum ada progress tracking.</td>
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

        (function () {
            const backdrop = document.getElementById('edit-progress-track-backdrop');
            const modal = document.getElementById('edit-progress-track-modal');
            const form = document.getElementById('edit-progress-track-form');

            if (!backdrop || !modal || !form) {
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

            document.querySelectorAll('[data-edit-progress-track]').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.action;
                    form.querySelector('#edit_generus_id').value = button.dataset.generusId;
                    form.querySelector('#edit_academic_year_id').value = button.dataset.academicYearId;
                    form.querySelector('#edit_semester_id').value = button.dataset.semesterId;
                    form.querySelector('#edit_period_label').value = button.dataset.periodLabel;
                    form.querySelector('#edit_overall_status').value = button.dataset.overallStatus;
                    form.querySelector('#edit_score').value = button.dataset.score;
                    form.querySelector('#edit_notes').value = button.dataset.notes;
                    form.querySelector('#edit_next_goal').value = button.dataset.nextGoal;
                    openModal();
                });
            });

            document.querySelectorAll('[data-edit-progress-track-close]').forEach(function (el) {
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
