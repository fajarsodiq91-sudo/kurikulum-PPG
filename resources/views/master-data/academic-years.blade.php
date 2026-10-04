@extends('layouts.app')

@section('title', 'Master Data Tahun Akademik')
@section('page-title', 'Master Data Tahun Akademik')
@section('breadcrumb', 'Master Data / Tahun Akademik')

@php
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100';
    $buttonClass = 'mt-4 rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800';
@endphp

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Master Data</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Tahun Akademik</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola tahun akademik yang dipakai modul KBM, evaluasi, dan laporan.</p>
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

    @if (\App\Support\Access::can('manage-master-data'))
    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('master-data.export', 'academic-years'), 'importUrl' => route('master-data.import', 'academic-years')])
    @endif

    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Tahun Akademik</h3>
        <p class="mt-1 text-sm text-slate-500">{{ $academicYears->count() }} data terdaftar.</p>
        @if (\App\Support\Access::can('manage-master-data'))
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
@endif
        <div class="mt-5 space-y-2 text-sm">
            @foreach ($academicYears as $year)
                <details class="border-t border-slate-100 pt-2">
                    <summary class="flex cursor-pointer list-none justify-between">
                        <span>{{ $year->name }} <span class="text-slate-400">({{ $year->code }})</span></span>
                        @if (\App\Support\Access::can('manage-master-data'))<span class="text-amber-600" title="Edit" aria-label="Edit"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></span>@endif
                    </summary>
                    @if (\App\Support\Access::can('manage-master-data'))
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
@endif
                </details>
            @endforeach
        </div>
    </section>
@endsection
