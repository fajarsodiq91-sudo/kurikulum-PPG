@extends('layouts.app')

@section('title', 'Kategori Materi')
@section('page-title', 'Kategori Materi')
@section('breadcrumb', 'Kurikulum / Kategori Materi')

@section('content')
    @php
        $formShouldOpen = $errors->any();
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Kategori Materi</h2>
            <p class="mt-2 text-sm text-slate-500">Kelompok materi untuk setiap kelas pada tiap semester.</p>
        </div>
        @if (\App\Support\Access::can('manage-learning-materials'))
            <button type="button" id="open-create-material-category" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                + Tambah Kategori
            </button>
        @endif
    </div>

    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('material-categories.export'), 'importUrl' => \App\Support\Access::can('manage-learning-materials') ? route('material-categories.import') : null])

    <x-filter-bar :action="route('material-categories.index')" search-placeholder="Cari nama atau kode kategori...">
        <x-filter-select name="class_grade_id" label="Kelas" :options="$classGrades" />
        <x-filter-select name="semester_id" label="Semester" :options="$semesters" />
    </x-filter-bar>

    @if (\App\Support\Access::can('manage-learning-materials'))
        <div id="create-material-category-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $formShouldOpen ? '' : 'hidden' }}" data-create-material-category-close></div>

        <div id="create-material-category-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $formShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-material-category-title">
            <div class="mx-auto my-6 w-full max-w-lg">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-material-category-close>&times;</button>
                    <h2 id="create-material-category-title" class="mb-4 text-xl font-semibold">Tambah Kategori</h2>
                    <form method="POST" action="{{ route('material-categories.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="class_grade_id">Kelas</label>
                            <select id="class_grade_id" name="class_grade_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">-- Pilih --</option>
                                @foreach($classGrades as $classGrade)
                                    <option value="{{ $classGrade->id }}" @selected(old('class_grade_id') == $classGrade->id)>{{ $classGrade->name }}</option>
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
                            <label class="block text-sm font-medium mb-2" for="name">Nama Kategori</label>
                            <input id="name" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('name') }}">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="code">Kode</label>
                            <input id="code" name="code" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('code') }}">
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
        <div id="edit-material-category-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50" data-edit-material-category-close></div>

        <div id="edit-material-category-modal" class="fixed inset-0 z-50 hidden overflow-y-auto p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="edit-material-category-title">
            <div class="mx-auto my-6 w-full max-w-lg">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-edit-material-category-close>&times;</button>
                    <h2 id="edit-material-category-title" class="mb-4 text-xl font-semibold">Ubah Kategori</h2>
                    <form method="POST" id="edit-material-category-form">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_class_grade_id">Kelas</label>
                            <select id="edit_class_grade_id" name="class_grade_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                @foreach($classGrades as $classGrade)
                                    <option value="{{ $classGrade->id }}">{{ $classGrade->name }}</option>
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
                            <label class="block text-sm font-medium mb-2" for="edit_name">Nama Kategori</label>
                            <input id="edit_name" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_code">Kode</label>
                            <input id="edit_code" name="code" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
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
            <h2 class="text-xl font-semibold mb-4">Daftar Kategori</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Kelas</th>
                            <th class="py-3 pr-4">Semester</th>
                            <th class="py-3 pr-4">Nama</th>
                            <th class="py-3 pr-4">Kode</th>
                            <th class="py-3 pr-4">Status</th>
                            @if (\App\Support\Access::can('manage-learning-materials'))
                                <th class="py-3 pr-4 text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">{{ $category->classGrade?->name }}</td>
                                <td class="py-3 pr-4">{{ $category->semester?->name }}</td>
                                <td class="py-3 pr-4">{{ $category->name }}</td>
                                <td class="py-3 pr-4">{{ $category->code }}</td>
                                <td class="py-3 pr-4">
                                    @if($category->is_active)
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
                                                data-edit-material-category
                                                data-action="{{ route('material-categories.update', $category) }}"
                                                data-class-grade-id="{{ $category->class_grade_id }}"
                                                data-semester-id="{{ $category->semester_id }}"
                                                data-name="{{ $category->name }}"
                                                data-code="{{ $category->code }}"
                                                data-description="{{ $category->description }}"
                                                data-is-active="{{ $category->is_active ? '1' : '0' }}"
                                            ><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>
                                            <form method="POST" action="{{ route('material-categories.destroy', $category) }}" onsubmit="return confirm(@js('Hapus kategori '.$category->name.'? Tindakan ini tidak dapat dibatalkan.'))">
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
                                <td colspan="6" class="py-6 text-center text-slate-500">Belum ada kategori materi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $categories->links() }}</div>
        </div>
    </div>

    <script>
        (function () {
            const openButton = document.getElementById('open-create-material-category');
            const backdrop = document.getElementById('create-material-category-backdrop');
            const modal = document.getElementById('create-material-category-modal');

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
            document.querySelectorAll('[data-create-material-category-close]').forEach(function (el) {
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
            const backdrop = document.getElementById('edit-material-category-backdrop');
            const modal = document.getElementById('edit-material-category-modal');
            const form = document.getElementById('edit-material-category-form');

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

            document.querySelectorAll('[data-edit-material-category]').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.action;
                    form.querySelector('#edit_class_grade_id').value = button.dataset.classGradeId;
                    form.querySelector('#edit_semester_id').value = button.dataset.semesterId;
                    form.querySelector('#edit_name').value = button.dataset.name;
                    form.querySelector('#edit_code').value = button.dataset.code;
                    form.querySelector('#edit_description').value = button.dataset.description;
                    form.querySelector('#edit_is_active').checked = button.dataset.isActive === '1';
                    openModal();
                });
            });

            document.querySelectorAll('[data-edit-material-category-close]').forEach(function (el) {
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
