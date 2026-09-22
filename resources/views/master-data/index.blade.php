@extends('layouts.app')

@section('title', 'Master Data')
@section('page-title', 'Master Data')
@section('breadcrumb', 'Master Data')

@php
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100';
    $buttonClass = 'mt-4 rounded-lg bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800';
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
            <div class="mt-5 space-y-2 text-sm">@foreach ($regions as $region)<div class="flex justify-between border-t border-slate-100 pt-2"><span>{{ $region->name }} <span class="text-slate-400">({{ $region->code }})</span></span><span>{{ $region->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>@endforeach</div>
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
            <div class="mt-5 space-y-2 text-sm">@foreach ($villages as $village)<div class="flex justify-between border-t border-slate-100 pt-2"><span>{{ $village->name }} <span class="text-slate-400">/ {{ $village->region?->name }}</span></span><span>{{ $village->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>@endforeach</div>
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
            <div class="mt-5 space-y-2 text-sm">@foreach ($groups as $group)<div class="border-t border-slate-100 pt-2">{{ $group->name }} <span class="text-slate-400">/ {{ $group->village?->name }}</span></div>@endforeach</div>
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
            <div class="mt-5 space-y-2 text-sm">@foreach ($levels as $level)<div class="flex justify-between border-t border-slate-100 pt-2"><span>{{ $level->name }} <span class="text-slate-400">({{ $level->code }})</span></span><span>{{ $level->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>@endforeach</div>
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
            <div class="mt-5 space-y-2 text-sm">@foreach ($academicYears as $year)<div class="border-t border-slate-100 pt-2">{{ $year->name }} <span class="text-slate-400">({{ $year->code }})</span></div>@endforeach</div>
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
            <div class="mt-5 space-y-2 text-sm">@foreach ($semesters as $semester)<div class="border-t border-slate-100 pt-2">{{ $semester->name }} <span class="text-slate-400">/ {{ $semester->academicYear?->name }}</span></div>@endforeach</div>
        </section>
    </div>
@endsection
