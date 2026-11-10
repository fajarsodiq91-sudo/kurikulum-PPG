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

    @if ($teacher->group === null)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Guru ini belum memiliki kelompok, sehingga daftar generus belum bisa ditampilkan. Isi Desa dan Kelompok pada data guru terlebih dahulu.</div>
    @endif

    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">
            @if ($teacher->group)
                Generus aktif di kelompok <span class="font-semibold text-slate-700">{{ $teacher->group->name }}</span>@if ($teacher->group->village) ({{ $teacher->group->village->name }})@endif.
            @endif
            Centang seluruh kelas KBM untuk menjadikan semua generusnya murid Anda, atau buka ("Lihat nama") sebuah kelas untuk memilih generus tertentu saja.
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

            <div class="mt-4 space-y-3" id="class-groups">
                @forelse ($candidatesByClass->sortBy(fn ($students, $classGradeId) => $students->first()->learningClass?->sort_order ?? PHP_INT_MAX) as $classGradeId => $students)
                    @php($classGrade = $students->first()->learningClass)
                    <div class="class-group rounded-xl border border-slate-200" data-name="{{ mb_strtolower($classGrade?->name ?? 'tanpa kelas') }}">
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <label class="flex cursor-pointer items-center gap-3 text-sm">
                                <input type="checkbox" class="class-checkbox h-4 w-4" data-class-group="{{ $classGradeId }}">
                                <span class="font-semibold text-slate-800">{{ $classGrade?->name ?? 'Tanpa Kelas KBM' }}</span>
                                <span class="text-xs text-slate-400">({{ $students->count() }} generus)</span>
                            </label>
                            <button type="button" class="toggle-class-details text-xs font-semibold text-brand-700 hover:text-brand-900" data-class-group="{{ $classGradeId }}">Lihat nama</button>
                        </div>

                        <ul class="hidden divide-y divide-slate-100 border-t border-slate-100" data-class-list="{{ $classGradeId }}">
                            @foreach ($students as $student)
                                <li class="student-row" data-name="{{ mb_strtolower($student->full_name) }}" data-class-group="{{ $classGradeId }}">
                                    <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 pl-10 text-sm">
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox h-4 w-4" data-class-group="{{ $classGradeId }}" @checked(in_array($student->id, old('student_ids', $selectedIds)))>
                                        <span class="font-medium text-slate-800">{{ $student->full_name }}</span>
                                        <span class="font-mono text-xs text-slate-400">{{ $student->registration_number }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-500">Belum ada generus aktif di kelompok ini.</p>
                @endforelse
            </div>

            <button type="submit" class="mt-6 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan Daftar Murid</button>
        </form>
    </section>

    <script>
        (function () {
            const groups = Array.from(document.querySelectorAll('.class-group'));
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

            function syncClassCheckbox(classId) {
                const classCheckbox = document.querySelector('.class-checkbox[data-class-group="' + classId + '"]');
                const studentCheckboxes = Array.from(document.querySelectorAll('.student-checkbox[data-class-group="' + classId + '"]'));

                if (!classCheckbox || studentCheckboxes.length === 0) {
                    return;
                }

                const checkedCount = studentCheckboxes.filter((input) => input.checked).length;
                classCheckbox.checked = checkedCount === studentCheckboxes.length;
                classCheckbox.indeterminate = checkedCount > 0 && checkedCount < studentCheckboxes.length;
            }

            function syncAllClassCheckboxes() {
                document.querySelectorAll('.class-checkbox').forEach((input) => syncClassCheckbox(input.dataset.classGroup));
            }

            search.addEventListener('input', () => {
                const term = search.value.trim().toLowerCase();
                rows.forEach((row) => { row.hidden = term !== '' && !row.dataset.name.includes(term); });

                groups.forEach((group) => {
                    const classId = group.querySelector('.class-checkbox').dataset.classGroup;
                    const groupRows = rows.filter((row) => row.dataset.classGroup === classId);
                    const nameMatches = term === '' || group.dataset.name.includes(term);
                    const hasVisibleStudent = groupRows.some((row) => !row.hidden);

                    group.hidden = term !== '' && !nameMatches && !hasVisibleStudent;

                    if (term !== '' && !nameMatches && hasVisibleStudent) {
                        const list = group.querySelector('[data-class-list]');
                        list.classList.remove('hidden');
                        group.querySelector('.toggle-class-details').textContent = 'Sembunyikan nama';
                    }
                });
            });

            document.getElementById('check-all').addEventListener('click', () => {
                visibleRows().forEach((row) => { row.querySelector('input').checked = true; });
                syncAllClassCheckboxes();
                updateCount();
            });
            document.getElementById('uncheck-all').addEventListener('click', () => {
                visibleRows().forEach((row) => { row.querySelector('input').checked = false; });
                syncAllClassCheckboxes();
                updateCount();
            });

            document.querySelectorAll('.toggle-class-details').forEach((button) => {
                button.addEventListener('click', () => {
                    const list = document.querySelector('[data-class-list="' + button.dataset.classGroup + '"]');
                    list.classList.toggle('hidden');
                    button.textContent = list.classList.contains('hidden') ? 'Lihat nama' : 'Sembunyikan nama';
                });
            });

            document.querySelectorAll('.class-checkbox').forEach((classCheckbox) => {
                classCheckbox.addEventListener('change', () => {
                    document.querySelectorAll('.student-checkbox[data-class-group="' + classCheckbox.dataset.classGroup + '"]').forEach((input) => {
                        input.checked = classCheckbox.checked;
                    });
                    classCheckbox.indeterminate = false;
                    updateCount();
                });
            });

            document.getElementById('class-groups').addEventListener('change', (event) => {
                if (event.target.classList.contains('student-checkbox')) {
                    syncClassCheckbox(event.target.dataset.classGroup);
                    updateCount();
                }
            });

            syncAllClassCheckboxes();
            updateCount();
        })();
    </script>
@endsection
