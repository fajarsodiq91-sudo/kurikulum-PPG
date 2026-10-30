@extends('layouts.app')

@section('title', 'Materi Pembelajaran')
@section('page-title', 'Materi Pembelajaran')
@section('breadcrumb', 'Kurikulum / Materi')

@section('content')
    @php
        $formShouldOpen = $errors->any();
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Materi Pembelajaran</h2>
            <p class="mt-2 text-sm text-slate-500">Materi detail di dalam setiap bab, kategori, kelas, dan semester.</p>
        </div>
        @if (\App\Support\Access::can('manage-learning-materials'))
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('learning-materials.export') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Export XLSX</a>
                <button type="button" id="open-create-learning-material" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    + Tambah Materi
                </button>
            </div>
        @endif
    </div>

    @if (\App\Support\Access::can('manage-learning-materials'))
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Import Materi dari XLSX</h2>
            <p class="mt-1 text-sm text-slate-500">
                Gunakan file XLSX hasil export agar kolom kelas, semester, kategori, dan bab dapat dipetakan dengan benar.
                Kategori dan bab yang belum ada akan otomatis dibuat.
            </p>
            <form method="POST" action="{{ route('learning-materials.import') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="file">File XLSX</label>
                    <input id="file" name="file" type="file" accept=".xlsx" required class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600">Import XLSX</button>
            </form>
        </section>
    @endif

    <x-filter-bar :action="route('learning-materials.index')" search-placeholder="Cari judul atau kode materi...">
        <x-filter-select name="material_chapter_id" label="Bab" :options="$chapters" option-label="name" />
        <x-filter-select name="academic_year_id" label="Tahun" :options="$academicYears" />
    </x-filter-bar>

    @if (\App\Support\Access::can('manage-learning-materials'))
        <div id="create-learning-material-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $formShouldOpen ? '' : 'hidden' }}" data-create-learning-material-close></div>

        <div id="create-learning-material-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $formShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-learning-material-title">
            <div class="mx-auto my-6 w-full max-w-lg">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-learning-material-close>&times;</button>
                    <h2 id="create-learning-material-title" class="mb-4 text-xl font-semibold">Tambah Materi</h2>
                    <form method="POST" action="{{ route('learning-materials.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="material_chapter_id">Bab</label>
                            <select id="material_chapter_id" name="material_chapter_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">-- Pilih --</option>
                                @foreach($chapters as $chapter)
                                    <option value="{{ $chapter->id }}" @selected(old('material_chapter_id') == $chapter->id)>
                                        {{ $chapter->materialCategory->classGrade?->name }} - {{ $chapter->materialCategory->semester?->name }} - {{ $chapter->materialCategory->name }} - {{ $chapter->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="title">Judul Materi</label>
                            <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('title') }}">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="code">Kode</label>
                            <input id="code" name="code" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('code') }}">
                        </div>
                        <details class="mb-4 rounded-lg border border-slate-200 px-3 py-2">
                            <summary class="cursor-pointer text-sm font-medium text-slate-600">Opsional: Minggu ke- (untuk Generate Jadwal KBM)</summary>
                            <div class="mt-3">
                                <input id="week_number" name="week_number" type="number" min="1" max="52" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('week_number') }}">
                                <p class="mt-1 text-xs text-slate-500">Hanya diisi jika materi ini akan dipakai untuk membuat jadwal KBM mingguan nasional.</p>
                            </div>
                        </details>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun (opsional)</label>
                            <select id="academic_year_id" name="academic_year_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">-- Semua Tahun --</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="description">Deskripsi</label>
                            <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                                Aktif
                            </label>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            Simpan
                        </button>
                    </form>
                </x-card>
            </div>
        </div>
    @endif

    @if (\App\Support\Access::can('manage-learning-materials'))
        <div id="edit-learning-material-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50" data-edit-learning-material-close></div>

        <div id="edit-learning-material-modal" class="fixed inset-0 z-50 hidden overflow-y-auto p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="edit-learning-material-title">
            <div class="mx-auto my-6 w-full max-w-lg">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-edit-learning-material-close>&times;</button>
                    <h2 id="edit-learning-material-title" class="mb-4 text-xl font-semibold">Ubah Materi</h2>
                    <form method="POST" id="edit-learning-material-form">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_material_chapter_id">Bab</label>
                            <select id="edit_material_chapter_id" name="material_chapter_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                @foreach($chapters as $chapter)
                                    <option value="{{ $chapter->id }}">
                                        {{ $chapter->materialCategory->classGrade?->name }} - {{ $chapter->materialCategory->semester?->name }} - {{ $chapter->materialCategory->name }} - {{ $chapter->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_title">Judul Materi</label>
                            <input id="edit_title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_code">Kode</label>
                            <input id="edit_code" name="code" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <details class="mb-4 rounded-lg border border-slate-200 px-3 py-2">
                            <summary class="cursor-pointer text-sm font-medium text-slate-600">Opsional: Minggu ke- (untuk Generate Jadwal KBM)</summary>
                            <div class="mt-3">
                                <input id="edit_week_number" name="week_number" type="number" min="1" max="52" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            </div>
                        </details>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_academic_year_id">Tahun (opsional)</label>
                            <select id="edit_academic_year_id" name="academic_year_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">-- Semua Tahun --</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_description">Deskripsi</label>
                            <textarea id="edit_description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input id="edit_is_active" type="checkbox" name="is_active" value="1">
                                Aktif
                            </label>
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                            Simpan Perubahan
                        </button>
                    </form>
                </x-card>
            </div>
        </div>
    @endif

    <div class="grid gap-6">
        <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
            <h2 class="text-xl font-semibold mb-4">Daftar Materi</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Kelas</th>
                            <th class="py-3 pr-4">Semester</th>
                            <th class="py-3 pr-4">Kategori</th>
                            <th class="py-3 pr-4">Bab</th>
                            <th class="py-3 pr-4">Materi</th>
                            <th class="py-3 pr-4">Kode</th>
                            <th class="py-3 pr-4">Tahun</th>
                            <th class="py-3 pr-4">Status</th>
                            @if (\App\Support\Access::can('manage-learning-materials'))
                                <th class="py-3 pr-4 text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">{{ $material->materialChapter->materialCategory->classGrade?->name }}</td>
                                <td class="py-3 pr-4">{{ $material->materialChapter->materialCategory->semester?->name }}</td>
                                <td class="py-3 pr-4">{{ $material->materialChapter->materialCategory->name }}</td>
                                <td class="py-3 pr-4">{{ $material->materialChapter->name }}</td>
                                <td class="py-3 pr-4">{{ $material->title }}</td>
                                <td class="py-3 pr-4">{{ $material->code }}</td>
                                <td class="py-3 pr-4">{{ $material->academicYear?->name ?? '-' }}</td>
                                <td class="py-3 pr-4">
                                    @if($material->is_active)
                                        <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                                @if (\App\Support\Access::can('manage-learning-materials'))
                                    <td class="py-3 pr-4 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-action-icon
                                                type="button"
                                                label="Ubah"
                                                color="amber"
                                                data-edit-learning-material
                                                data-action="{{ route('learning-materials.update', $material) }}"
                                                data-material-chapter-id="{{ $material->material_chapter_id }}"
                                                data-title="{{ $material->title }}"
                                                data-code="{{ $material->code }}"
                                                data-week-number="{{ $material->week_number }}"
                                                data-academic-year-id="{{ $material->academic_year_id }}"
                                                data-description="{{ $material->description }}"
                                                data-is-active="{{ $material->is_active ? '1' : '0' }}"
                                            ><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>
                                            <form method="POST" action="{{ route('learning-materials.destroy', $material) }}" onsubmit="return confirm(@js('Hapus materi '.$material->title.'? Tindakan ini tidak dapat dibatalkan.'))">
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
                                <td colspan="9" class="py-6 text-center text-slate-500">Belum ada materi pembelajaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $materials->links() }}</div>
        </div>
    </div>

    <script>
        (function () {
            const openButton = document.getElementById('open-create-learning-material');
            const backdrop = document.getElementById('create-learning-material-backdrop');
            const modal = document.getElementById('create-learning-material-modal');

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
            document.querySelectorAll('[data-create-learning-material-close]').forEach(function (el) {
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
            const backdrop = document.getElementById('edit-learning-material-backdrop');
            const modal = document.getElementById('edit-learning-material-modal');
            const form = document.getElementById('edit-learning-material-form');

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

            document.querySelectorAll('[data-edit-learning-material]').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.action;
                    form.querySelector('#edit_material_chapter_id').value = button.dataset.materialChapterId;
                    form.querySelector('#edit_title').value = button.dataset.title;
                    form.querySelector('#edit_code').value = button.dataset.code;
                    form.querySelector('#edit_week_number').value = button.dataset.weekNumber;
                    form.querySelector('#edit_academic_year_id').value = button.dataset.academicYearId;
                    form.querySelector('#edit_description').value = button.dataset.description;
                    form.querySelector('#edit_is_active').checked = button.dataset.isActive === '1';
                    openModal();
                });
            });

            document.querySelectorAll('[data-edit-learning-material-close]').forEach(function (el) {
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
