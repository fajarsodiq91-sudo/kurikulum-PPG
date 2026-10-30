@extends('layouts.app')

@section('title', 'Download File')
@section('page-title', 'Download File')
@section('breadcrumb', 'Kurikulum / Download File')

@section('content')
    @php
        $formShouldOpen = $errors->any();
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Download File</h2>
            <p class="mt-2 text-sm text-slate-500">Kumpulan link file yang dapat diunduh oleh pengguna.</p>
        </div>
        @if (\App\Support\Access::can('manage-download-files'))
            <button type="button" id="open-create-download-file" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                + Tambah File
            </button>
        @endif
    </div>

    <x-filter-bar :action="route('download-files.index')" search-placeholder="Cari judul file..." />

    @if (\App\Support\Access::can('manage-download-files'))
        <div id="create-download-file-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $formShouldOpen ? '' : 'hidden' }}" data-create-download-file-close></div>

        <div id="create-download-file-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $formShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-download-file-title">
            <div class="mx-auto my-6 w-full max-w-lg">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-download-file-close>&times;</button>
                    <h2 id="create-download-file-title" class="mb-4 text-xl font-semibold">Tambah File</h2>
                    <form method="POST" action="{{ route('download-files.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="title">Judul</label>
                            <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('title') }}">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="file_url">Link File</label>
                            <input id="file_url" name="file_url" type="url" required placeholder="https://..." class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('file_url') }}">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="description">Deskripsi</label>
                            <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="sort_order">Urutan</label>
                            <input id="sort_order" name="sort_order" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('sort_order', 0) }}">
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

    @if (\App\Support\Access::can('manage-download-files'))
        <div id="edit-download-file-backdrop" class="fixed inset-0 z-40 hidden bg-slate-900/50" data-edit-download-file-close></div>

        <div id="edit-download-file-modal" class="fixed inset-0 z-50 hidden overflow-y-auto p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="edit-download-file-title">
            <div class="mx-auto my-6 w-full max-w-lg">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-edit-download-file-close>&times;</button>
                    <h2 id="edit-download-file-title" class="mb-4 text-xl font-semibold">Ubah File</h2>
                    <form method="POST" id="edit-download-file-form">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_title">Judul</label>
                            <input id="edit_title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_file_url">Link File</label>
                            <input id="edit_file_url" name="file_url" type="url" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_description">Deskripsi</label>
                            <textarea id="edit_description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="edit_sort_order">Urutan</label>
                            <input id="edit_sort_order" name="sort_order" type="number" min="0" class="w-full rounded-lg border border-slate-300 px-3 py-2">
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
            <h2 class="text-xl font-semibold mb-4">Daftar File</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Judul</th>
                            <th class="py-3 pr-4">Deskripsi</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($files as $file)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $file->title }}</td>
                                <td class="py-3 pr-4 text-slate-500">{{ $file->description }}</td>
                                <td class="py-3 pr-4">
                                    @if($file->is_active)
                                        <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ $file->file_url }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                                            Download
                                        </a>
                                        @if (\App\Support\Access::can('manage-download-files'))
                                            <x-action-icon
                                                type="button"
                                                label="Ubah"
                                                color="amber"
                                                data-edit-download-file
                                                data-action="{{ route('download-files.update', $file) }}"
                                                data-title="{{ $file->title }}"
                                                data-file-url="{{ $file->file_url }}"
                                                data-description="{{ $file->description }}"
                                                data-sort-order="{{ $file->sort_order }}"
                                                data-is-active="{{ $file->is_active ? '1' : '0' }}"
                                            ><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>
                                            <form method="POST" action="{{ route('download-files.destroy', $file) }}" onsubmit="return confirm(@js('Hapus file '.$file->title.'? Tindakan ini tidak dapat dibatalkan.'))">
                                                @csrf
                                                @method('DELETE')
                                                <x-action-icon label="Hapus" color="rose" type="submit"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></x-action-icon>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-500">Belum ada file unduhan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $files->links() }}</div>
        </div>
    </div>

    <script>
        (function () {
            const openButton = document.getElementById('open-create-download-file');
            const backdrop = document.getElementById('create-download-file-backdrop');
            const modal = document.getElementById('create-download-file-modal');

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
            document.querySelectorAll('[data-create-download-file-close]').forEach(function (el) {
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
            const backdrop = document.getElementById('edit-download-file-backdrop');
            const modal = document.getElementById('edit-download-file-modal');
            const form = document.getElementById('edit-download-file-form');

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

            document.querySelectorAll('[data-edit-download-file]').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = button.dataset.action;
                    form.querySelector('#edit_title').value = button.dataset.title;
                    form.querySelector('#edit_file_url').value = button.dataset.fileUrl;
                    form.querySelector('#edit_description').value = button.dataset.description;
                    form.querySelector('#edit_sort_order').value = button.dataset.sortOrder;
                    form.querySelector('#edit_is_active').checked = button.dataset.isActive === '1';
                    openModal();
                });
            });

            document.querySelectorAll('[data-edit-download-file-close]').forEach(function (el) {
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
