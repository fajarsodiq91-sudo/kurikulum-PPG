@extends('layouts.app')

@section('title', 'Materi Pembelajaran')
@section('page-title', 'Materi Pembelajaran')
@section('breadcrumb', 'Kurikulum / Materi')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Materi Pembelajaran</h2>
            <p class="mt-2 text-sm text-slate-500">Materi detail di dalam setiap bab, kategori, kelas, dan semester.</p>
        </div>
        @if (\App\Support\Access::can('manage-learning-materials'))
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('learning-materials.export') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Export XLSX</a>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
            <p class="font-semibold">Import XLSX gagal.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        @if (\App\Support\Access::can('manage-learning-materials'))
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Materi</h2>
                <form method="POST" action="{{ route('learning-materials.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="material_chapter_id">Bab</label>
                        <select id="material_chapter_id" name="material_chapter_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($chapters as $chapter)
                                <option value="{{ $chapter->id }}">
                                    {{ $chapter->materialCategory->classGrade?->name }} - {{ $chapter->materialCategory->semester?->name }} - {{ $chapter->materialCategory->name }} - {{ $chapter->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="title">Judul Materi</label>
                        <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="code">Kode</label>
                        <input id="code" name="code" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun (opsional)</label>
                        <select id="academic_year_id" name="academic_year_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Semua Tahun --</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-slate-500">Belum ada materi pembelajaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $materials->links() }}</div>
        </div>
    </div>
@endsection
