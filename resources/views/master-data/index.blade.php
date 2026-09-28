@extends('layouts.app')

@section('title', 'Master Data')
@section('page-title', 'Master Data')
@section('breadcrumb', 'Master Data')

@php
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100';
    $buttonClass = 'mt-4 rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800';
@endphp

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Konfigurasi Sistem</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Master Data Dinamis</h2>
        <p class="mt-2 text-sm text-slate-500">Tambahkan dan kelola data yang digunakan oleh modul generus, KBM, evaluasi, dan laporan.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">Data belum dapat disimpan.</p>
            <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Daerah</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $regions->count() }} data terdaftar.</p>
            <form method="POST" action="{{ route('master-data.regions.store') }}" class="mt-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <input class="{{ $inputClass }}" name="name" placeholder="Nama daerah" required>
                    <input class="{{ $inputClass }}" name="code" placeholder="Kode, contoh KRT" required>
                </div>
                <textarea class="{{ $inputClass }} mt-3" name="description" rows="2" placeholder="Deskripsi (opsional)"></textarea>
                <input type="hidden" name="is_active" value="1">
                <button class="{{ $buttonClass }}">Tambah Daerah</button>
            </form>
            <div class="mt-5 space-y-2 text-sm">
                @foreach ($regions as $region)
                    <details class="border-t border-slate-100 pt-2">
                        <summary class="flex cursor-pointer list-none justify-between">
                            <span>{{ $region->name }} <span class="text-slate-400">({{ $region->code }})</span></span>
                            <span class="flex items-center gap-3"><span>{{ $region->is_active ? 'Aktif' : 'Nonaktif' }}</span><span class="text-amber-600 underline">Edit</span></span>
                        </summary>
                        <form method="POST" action="{{ route('master-data.regions.update', $region) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="{{ $inputClass }}" name="name" value="{{ $region->name }}" required>
                                <input class="{{ $inputClass }}" name="code" value="{{ $region->code }}" required>
                            </div>
                            <textarea class="{{ $inputClass }} mt-3" name="description" rows="2" placeholder="Deskripsi (opsional)">{{ $region->description }}</textarea>
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($region->is_active)>
                                Aktif
                            </label>
                            <button class="mt-3 block rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Perubahan</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Desa</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $villages->count() }} data terdaftar.</p>
            <form method="POST" action="{{ route('master-data.villages.store') }}" class="mt-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <select class="{{ $inputClass }}" name="region_id" required><option value="">Pilih daerah</option>@foreach ($regions->where('is_active', true) as $region)<option value="{{ $region->id }}">{{ $region->name }}</option>@endforeach</select>
                    <input class="{{ $inputClass }}" name="name" placeholder="Nama desa" required>
                    <input class="{{ $inputClass }}" name="code" placeholder="Kode desa">
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="{{ $buttonClass }}">Tambah Desa</button>
            </form>
            <div class="mt-5 space-y-2 text-sm">
                @foreach ($villages as $village)
                    <details class="border-t border-slate-100 pt-2">
                        <summary class="flex cursor-pointer list-none justify-between">
                            <span>{{ $village->name }} <span class="text-slate-400">/ {{ $village->region?->name }}</span></span>
                            <span class="flex items-center gap-3"><span>{{ $village->is_active ? 'Aktif' : 'Nonaktif' }}</span><span class="text-amber-600 underline">Edit</span></span>
                        </summary>
                        <form method="POST" action="{{ route('master-data.villages.update', $village) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <select class="{{ $inputClass }}" name="region_id" required>
                                    @foreach ($regions->where('is_active', true) as $region)
                                        <option value="{{ $region->id }}" @selected($village->region_id === $region->id)>{{ $region->name }}</option>
                                    @endforeach
                                </select>
                                <input class="{{ $inputClass }}" name="name" value="{{ $village->name }}" required>
                                <input class="{{ $inputClass }}" name="code" value="{{ $village->code }}">
                            </div>
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($village->is_active)>
                                Aktif
                            </label>
                            <button class="mt-3 block rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Perubahan</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Kelompok</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $groups->count() }} data terdaftar.</p>
            <form method="POST" action="{{ route('master-data.groups.store') }}" class="mt-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <select class="{{ $inputClass }}" name="village_id" required><option value="">Pilih desa</option>@foreach ($villages->where('is_active', true) as $village)<option value="{{ $village->id }}">{{ $village->name }}</option>@endforeach</select>
                    <input class="{{ $inputClass }}" name="name" placeholder="Nama kelompok" required>
                    <input class="{{ $inputClass }}" name="code" placeholder="Kode kelompok">
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="{{ $buttonClass }}">Tambah Kelompok</button>
            </form>
            <div class="mt-5 space-y-2 text-sm">
                @foreach ($groups as $group)
                    <details class="border-t border-slate-100 pt-2">
                        <summary class="flex cursor-pointer list-none justify-between">
                            <span>{{ $group->name }} <span class="text-slate-400">/ {{ $group->village?->name }}</span></span>
                            <span class="text-amber-600 underline">Edit</span>
                        </summary>
                        <form method="POST" action="{{ route('master-data.groups.update', $group) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <select class="{{ $inputClass }}" name="village_id" required>
                                    @foreach ($villages->where('is_active', true) as $village)
                                        <option value="{{ $village->id }}" @selected($group->village_id === $village->id)>{{ $village->name }}</option>
                                    @endforeach
                                </select>
                                <input class="{{ $inputClass }}" name="name" value="{{ $group->name }}" required>
                                <input class="{{ $inputClass }}" name="code" value="{{ $group->code }}">
                            </div>
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($group->is_active)>
                                Aktif
                            </label>
                            <button class="mt-3 block rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Perubahan</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Jenjang</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $levels->count() }} data terdaftar.</p>
            <form method="POST" action="{{ route('master-data.levels.store') }}" class="mt-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <input class="{{ $inputClass }}" name="name" placeholder="Nama jenjang" required>
                    <input class="{{ $inputClass }}" name="code" placeholder="Kode jenjang" required>
                    <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="0" placeholder="Urutan" required>
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="{{ $buttonClass }}">Tambah Jenjang</button>
            </form>
            <div class="mt-5 space-y-2 text-sm">
                @foreach ($levels as $level)
                    <details class="border-t border-slate-100 pt-2">
                        <summary class="flex cursor-pointer list-none justify-between">
                            <span>{{ $level->name }} <span class="text-slate-400">({{ $level->code }})</span></span>
                            <span class="flex items-center gap-3"><span>{{ $level->is_active ? 'Aktif' : 'Nonaktif' }}</span><span class="text-amber-600 underline">Edit</span></span>
                        </summary>
                        <form method="POST" action="{{ route('master-data.levels.update', $level) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="{{ $inputClass }}" name="name" value="{{ $level->name }}" required>
                                <input class="{{ $inputClass }}" name="code" value="{{ $level->code }}" required>
                                <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="{{ $level->sort_order }}" required>
                            </div>
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($level->is_active)>
                                Aktif
                            </label>
                            <button class="mt-3 block rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Perubahan</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Kelas</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $classGrades->count() }} data terdaftar. Dipakai untuk pilihan Kelas Sekolah dan Kelas KBM pada data generus.</p>
            <form method="POST" action="{{ route('master-data.class-grades.store') }}" class="mt-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <input class="{{ $inputClass }}" name="name" placeholder="Nama kelas, contoh Kelas 4" required>
                    <input class="{{ $inputClass }}" name="code" placeholder="Kode kelas" required>
                    <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="0" placeholder="Urutan" required>
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="{{ $buttonClass }}">Tambah Kelas</button>
            </form>
            <div class="mt-5 space-y-2 text-sm">
                @foreach ($classGrades as $classGrade)
                    <details class="border-t border-slate-100 pt-2">
                        <summary class="flex cursor-pointer list-none justify-between">
                            <span>{{ $classGrade->name }} <span class="text-slate-400">({{ $classGrade->code }})</span></span>
                            <span class="flex items-center gap-3"><span>{{ $classGrade->is_active ? 'Aktif' : 'Nonaktif' }}</span><span class="text-amber-600 underline">Edit</span></span>
                        </summary>
                        <form method="POST" action="{{ route('master-data.class-grades.update', $classGrade) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="{{ $inputClass }}" name="name" value="{{ $classGrade->name }}" required>
                                <input class="{{ $inputClass }}" name="code" value="{{ $classGrade->code }}" required>
                                <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="{{ $classGrade->sort_order }}" required>
                            </div>
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($classGrade->is_active)>
                                Aktif
                            </label>
                            <button class="mt-3 block rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Perubahan</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Tahun Akademik</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $academicYears->count() }} data terdaftar.</p>
            <form method="POST" action="{{ route('master-data.academic-years.store') }}" class="mt-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <input class="{{ $inputClass }}" name="name" placeholder="Contoh 2025/2026" required>
                    <input class="{{ $inputClass }}" name="code" placeholder="Kode" required>
                    <input class="{{ $inputClass }}" name="start_year" type="number" min="2000" max="9999" placeholder="Tahun mulai" required>
                    <input class="{{ $inputClass }}" name="end_year" type="number" min="2000" max="9999" placeholder="Tahun selesai" required>
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="{{ $buttonClass }}">Tambah Tahun Akademik</button>
            </form>
            <div class="mt-5 space-y-2 text-sm">
                @foreach ($academicYears as $year)
                    <details class="border-t border-slate-100 pt-2">
                        <summary class="flex cursor-pointer list-none justify-between">
                            <span>{{ $year->name }} <span class="text-slate-400">({{ $year->code }})</span></span>
                            <span class="text-amber-600 underline">Edit</span>
                        </summary>
                        <form method="POST" action="{{ route('master-data.academic-years.update', $year) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input class="{{ $inputClass }}" name="name" value="{{ $year->name }}" required>
                                <input class="{{ $inputClass }}" name="code" value="{{ $year->code }}" required>
                                <input class="{{ $inputClass }}" name="start_year" type="number" min="2000" max="9999" value="{{ $year->start_year }}" required>
                                <input class="{{ $inputClass }}" name="end_year" type="number" min="2000" max="9999" value="{{ $year->end_year }}" required>
                            </div>
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($year->is_active)>
                                Aktif
                            </label>
                            <button class="mt-3 block rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Perubahan</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">Semester</h3>
            <p class="mt-1 text-sm text-slate-500">{{ $semesters->count() }} data terdaftar.</p>
            <form method="POST" action="{{ route('master-data.semesters.store') }}" class="mt-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <select class="{{ $inputClass }}" name="academic_year_id" required><option value="">Pilih tahun akademik</option>@foreach ($academicYears->where('is_active', true) as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select>
                    <input class="{{ $inputClass }}" name="name" placeholder="Contoh Semester 1" required>
                    <input class="{{ $inputClass }}" name="code" placeholder="Kode semester">
                    <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="0" required>
                </div>
                <input type="hidden" name="is_active" value="1">
                <button class="{{ $buttonClass }}">Tambah Semester</button>
            </form>
            <div class="mt-5 space-y-2 text-sm">
                @foreach ($semesters as $semester)
                    <details class="border-t border-slate-100 pt-2">
                        <summary class="flex cursor-pointer list-none justify-between">
                            <span>{{ $semester->name }} <span class="text-slate-400">/ {{ $semester->academicYear?->name }}</span></span>
                            <span class="text-amber-600 underline">Edit</span>
                        </summary>
                        <form method="POST" action="{{ route('master-data.semesters.update', $semester) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <select class="{{ $inputClass }}" name="academic_year_id" required>
                                    @foreach ($academicYears->where('is_active', true) as $year)
                                        <option value="{{ $year->id }}" @selected($semester->academic_year_id === $year->id)>{{ $year->name }}</option>
                                    @endforeach
                                </select>
                                <input class="{{ $inputClass }}" name="name" value="{{ $semester->name }}" required>
                                <input class="{{ $inputClass }}" name="code" value="{{ $semester->code }}">
                                <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="{{ $semester->sort_order }}" required>
                            </div>
                            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($semester->is_active)>
                                Aktif
                            </label>
                            <button class="mt-3 block rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Perubahan</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>
    </div>
@endsection
