@extends('layouts.app')

@section('title', 'Klasifikasi Materi')
@section('page-title', 'Klasifikasi Materi')
@section('breadcrumb', 'Kurikulum / Klasifikasi Materi')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Klasifikasi Materi</h2>
        <p class="mt-2 text-sm text-slate-500">Tentukan jenis setiap materi: bacaan, hafalan, menulis, praktik ibadah, akhlaq, atau kemandirian.</p>
    </div>

    <x-filter-bar :action="route('material-classifications.index')" search-placeholder="Cari judul atau kode materi...">
        <x-filter-select name="material_chapter_id" label="Bab" :options="$chapters" option-label="name" />
        <x-filter-select name="jenis" label="Jenis" :options="$categories" />
    </x-filter-bar>

    <div class="grid gap-6">
        <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
            <h2 class="text-xl font-semibold mb-4">Daftar Materi</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Kategori</th>
                            <th class="py-3 pr-4">Bab</th>
                            <th class="py-3 pr-4">Materi</th>
                            <th class="py-3 pr-4">Kode</th>
                            <th class="py-3 pr-4">Jenis</th>
                            @if (\App\Support\Access::can('manage-learning-materials'))
                                <th class="py-3 pr-4 text-right">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($materials as $material)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">{{ $material->materialChapter->materialCategory->name }}</td>
                                <td class="py-3 pr-4">{{ $material->materialChapter->name }}</td>
                                <td class="py-3 pr-4">{{ $material->title }}</td>
                                <td class="py-3 pr-4">{{ $material->code }}</td>
                                @if (\App\Support\Access::can('manage-learning-materials'))
                                    <td class="py-3 pr-4" colspan="2">
                                        <form method="POST" action="{{ route('material-classifications.update', $material) }}" class="flex items-center justify-end gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="jenis" class="w-full max-w-[12rem] rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                                <option value="">-- Belum diklasifikasi --</option>
                                                @foreach($categories as $value => $label)
                                                    <option value="{{ $value }}" @selected($material->jenis === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="shrink-0 rounded-lg bg-brand-600 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                                        </form>
                                    </td>
                                @else
                                    <td class="py-3 pr-4">{{ $categories[$material->jenis] ?? '-' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500">Belum ada materi pembelajaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $materials->links() }}</div>
        </div>
    </div>
@endsection
