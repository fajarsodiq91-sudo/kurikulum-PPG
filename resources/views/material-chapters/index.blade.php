@extends('layouts.app')

@section('title', 'Bab Materi')
@section('page-title', 'Bab Materi')
@section('breadcrumb', 'Kurikulum / Bab Materi')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Bab Materi</h2>
        <p class="mt-2 text-sm text-slate-500">Bab-bab di dalam setiap kategori materi.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        @if (\App\Support\Access::can('manage-learning-materials'))
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Bab</h2>
                <form method="POST" action="{{ route('material-chapters.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="material_category_id">Kategori</label>
                        <select id="material_category_id" name="material_category_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->classGrade?->name }} - {{ $category->semester?->name }} - {{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="name">Nama Bab</label>
                        <input id="name" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="code">Kode</label>
                        <input id="code" name="code" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="description">Deskripsi</label>
                        <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Aktif
                        </label>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                        Simpan
                    </button>
                </form>
            </div>
        @endif

        <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
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
@endsection
