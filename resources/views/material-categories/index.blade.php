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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-500">Belum ada kategori materi.</td>
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
    </script>
@endsection
