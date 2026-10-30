@extends('layouts.app')

@section('title', 'Bab Materi')
@section('page-title', 'Bab Materi')
@section('breadcrumb', 'Kurikulum / Bab Materi')

@section('content')
    @php
        $formShouldOpen = $errors->any();
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Bab Materi</h2>
            <p class="mt-2 text-sm text-slate-500">Bab-bab di dalam setiap kategori materi.</p>
        </div>
        @if (\App\Support\Access::can('manage-learning-materials'))
            <button type="button" id="open-create-material-chapter" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                + Tambah Bab
            </button>
        @endif
    </div>

    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('material-chapters.export'), 'importUrl' => \App\Support\Access::can('manage-learning-materials') ? route('material-chapters.import') : null])

    <x-filter-bar :action="route('material-chapters.index')" search-placeholder="Cari nama atau kode bab...">
        <x-filter-select name="material_category_id" label="Kategori" :options="$categories" />
    </x-filter-bar>

    @if (\App\Support\Access::can('manage-learning-materials'))
        <div id="create-material-chapter-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $formShouldOpen ? '' : 'hidden' }}" data-create-material-chapter-close></div>

        <div id="create-material-chapter-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $formShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-material-chapter-title">
            <div class="mx-auto my-6 w-full max-w-lg">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-material-chapter-close>&times;</button>
                    <h2 id="create-material-chapter-title" class="mb-4 text-xl font-semibold">Tambah Bab</h2>
                    <form method="POST" action="{{ route('material-chapters.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="material_category_id">Kategori</label>
                            <select id="material_category_id" name="material_category_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <option value="">-- Pilih --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('material_category_id') == $category->id)>{{ $category->classGrade?->name }} - {{ $category->semester?->name }} - {{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="name">Nama Bab</label>
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
            <h2 class="text-xl font-semibold mb-4">Daftar Bab</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Kelas</th>
                            <th class="py-3 pr-4">Semester</th>
                            <th class="py-3 pr-4">Kategori</th>
                            <th class="py-3 pr-4">Bab</th>
                            <th class="py-3 pr-4">Kode</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chapters as $chapter)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">{{ $chapter->materialCategory->classGrade?->name }}</td>
                                <td class="py-3 pr-4">{{ $chapter->materialCategory->semester?->name }}</td>
                                <td class="py-3 pr-4">{{ $chapter->materialCategory->name }}</td>
                                <td class="py-3 pr-4">{{ $chapter->name }}</td>
                                <td class="py-3 pr-4">{{ $chapter->code }}</td>
                                <td class="py-3 pr-4">
                                    @if($chapter->is_active)
                                        <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500">Belum ada bab materi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $chapters->links() }}</div>
        </div>
    </div>

    <script>
        (function () {
            const openButton = document.getElementById('open-create-material-chapter');
            const backdrop = document.getElementById('create-material-chapter-backdrop');
            const modal = document.getElementById('create-material-chapter-modal');

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
            document.querySelectorAll('[data-create-material-chapter-close]').forEach(function (el) {
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
