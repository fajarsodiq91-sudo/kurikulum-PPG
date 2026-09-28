@extends('layouts.app')

@section('title', 'Murid Guru')
@section('page-title', 'Murid Guru')
@section('breadcrumb', 'Guru / Murid')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Guru</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Murid {{ $teacher->name }}</h2>
        <p class="mt-2 text-sm text-slate-500">Centang generus yang menjadi murid. Data murid yang dicentang akan muncul di absensi, evaluasi, tindak lanjut, rapor, dan komunikasi orang tua saat guru login.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @include('layouts.partials.validation-errors')

    @if ($teacher->group === null)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Guru ini belum memiliki kelompok, sehingga daftar generus belum bisa ditampilkan. Isi Desa dan Kelompok pada data guru terlebih dahulu.</div>
    @endif

    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">
            @if ($teacher->group)
                Generus aktif di kelompok <span class="font-semibold text-slate-700">{{ $teacher->group->name }}</span>@if ($teacher->group->village) ({{ $teacher->group->village->name }})@endif.
            @endif
            <span id="selected-count" class="font-semibold text-slate-700"></span>
        </p>

        <form method="POST" action="{{ $action }}" class="mt-4">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <input id="student-search" type="search" placeholder="Cari nama generus" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100 sm:max-w-xs">
                <button type="button" id="check-all" class="text-sm font-semibold text-amber-600 hover:text-amber-700">Centang semua yang tampil</button>
                <button type="button" id="uncheck-all" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Hapus centang</button>
            </div>

            <ul class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200" id="student-list">
                @forelse ($candidates as $student)
                    <li class="student-row" data-name="{{ mb_strtolower($student->full_name) }}">
                        <label class="flex cursor-pointer items-center gap-3 px-4 py-3 text-sm">
                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox h-4 w-4" @checked(in_array($student->id, old('student_ids', $selectedIds)))>
                            <span class="font-medium text-slate-800">{{ $student->full_name }}</span>
                            <span class="font-mono text-xs text-slate-400">{{ $student->registration_number }}</span>
                        </label>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-slate-500">Belum ada generus aktif di kelompok ini.</li>
                @endforelse
            </ul>

            <button type="submit" class="mt-6 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan Daftar Murid</button>
        </form>
    </section>

    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('.student-row'));
            const search = document.getElementById('student-search');
            const counter = document.getElementById('selected-count');

            function visibleRows() {
                return rows.filter((row) => !row.hidden);
            }

            function updateCount() {
                const checked = document.querySelectorAll('.student-checkbox:checked').length;
                counter.textContent = checked + ' murid dicentang.';
            }

            search.addEventListener('input', () => {
                const term = search.value.trim().toLowerCase();
                rows.forEach((row) => { row.hidden = term !== '' && !row.dataset.name.includes(term); });
            });
            document.getElementById('check-all').addEventListener('click', () => {
                visibleRows().forEach((row) => { row.querySelector('input').checked = true; });
                updateCount();
            });
            document.getElementById('uncheck-all').addEventListener('click', () => {
                visibleRows().forEach((row) => { row.querySelector('input').checked = false; });
                updateCount();
            });
            document.getElementById('student-list').addEventListener('change', updateCount);
            updateCount();
        })();
    </script>
@endsection
