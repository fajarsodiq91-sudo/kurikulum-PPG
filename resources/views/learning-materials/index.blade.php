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
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="week_number">Minggu ke- (dalam semester)</label>
                            <input id="week_number" name="week_number" type="number" min="1" max="52" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('week_number') }}">
                            <p class="mt-1 text-xs text-slate-500">Dipakai untuk membuat jadwal KBM mingguan yang sama di seluruh kelompok.</p>
                        </div>
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
                            <th class="py-3 pr-4">Minggu</th>
                            <th class="py-3 pr-4">Kode</th>
                            <th class="py-3 pr-4">Tahun</th>
                            <th class="py-3 pr-4">Status</th>
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
                                <td class="py-3 pr-4">{{ $material->week_number ?? '-' }}</td>
                                <td class="py-3 pr-4">{{ $material->code }}</td>
                                <td class="py-3 pr-4">{{ $material->academicYear?->name ?? '-' }}</td>
                                <td class="py-3 pr-4">
                                    @if($material->is_active)
                                        <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
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
    </script>
@endsection
