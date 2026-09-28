@extends('layouts.app')

@section('title', 'Master Data Semester')
@section('page-title', 'Master Data Semester')
@section('breadcrumb', 'Master Data / Semester')

@php
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100';
    $buttonClass = 'mt-4 rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800';
@endphp

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Master Data</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Semester</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola semester pada setiap tahun akademik.</p>
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

    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('master-data.export', 'semesters'), 'importUrl' => route('master-data.import', 'semesters')])

    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
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
@endsection
