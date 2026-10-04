@extends('layouts.app')

@section('title', 'Master Data Konversi Nilai')
@section('page-title', 'Master Data Konversi Nilai')
@section('breadcrumb', 'Master Data / Konversi Nilai')

@php
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100';
    $buttonClass = 'mt-4 rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800';
@endphp

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Master Data</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Konversi Nilai</h2>
        <p class="mt-2 text-sm text-slate-500">Rentang nilai munaqosah, huruf, dan keterangannya. Saat nilai munaqosah diisi tanpa hasil, sistem memakai tabel ini untuk menentukan huruf dan keterangannya secara otomatis.</p>
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
    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('master-data.export', 'grade-scales'), 'importUrl' => route('master-data.import', 'grade-scales')])
    @endif

    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Konversi Nilai</h3>
        <p class="mt-1 text-sm text-slate-500">{{ $gradeScales->count() }} data terdaftar.</p>
        @if (\App\Support\Access::can('manage-master-data'))
<form method="POST" action="{{ route('master-data.grade-scales.store') }}" class="mt-4">
            @csrf
            <div class="grid gap-3 sm:grid-cols-3">
                <input class="{{ $inputClass }}" name="grade" placeholder="Huruf, misal A" required>
                <input class="{{ $inputClass }}" name="min_score" type="number" min="0" max="100" placeholder="Nilai minimal" required>
                <input class="{{ $inputClass }}" name="max_score" type="number" min="0" max="100" placeholder="Nilai maksimal" required>
            </div>
            <textarea class="{{ $inputClass }} mt-3" name="description" rows="2" placeholder="Keterangan, misal: Generus memahami materi dan mampu mempraktikkannya dengan sempurna"></textarea>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="0" placeholder="Urutan" required>
            </div>
            <input type="hidden" name="is_active" value="1">
            <button class="{{ $buttonClass }}">Tambah Konversi Nilai</button>
        </form>
@endif
        <div class="mt-5 space-y-2 text-sm">
            @foreach ($gradeScales as $gradeScale)
                <details class="border-t border-slate-100 pt-2">
                    <summary class="flex cursor-pointer list-none justify-between">
                        <span>{{ $gradeScale->grade }} <span class="text-slate-400">({{ $gradeScale->min_score }}-{{ $gradeScale->max_score }})</span></span>
                        <span class="flex items-center gap-3"><span>{{ $gradeScale->is_active ? 'Aktif' : 'Nonaktif' }}</span>@if (\App\Support\Access::can('manage-master-data'))<span class="text-amber-600" title="Edit" aria-label="Edit"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></span>@endif</span>
                    </summary>
                    <p class="mt-2 text-slate-500">{{ $gradeScale->description ?? '-' }}</p>
                    @if (\App\Support\Access::can('manage-master-data'))
<form method="POST" action="{{ route('master-data.grade-scales.update', $gradeScale) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-3 sm:grid-cols-3">
                            <input class="{{ $inputClass }}" name="grade" value="{{ $gradeScale->grade }}" required>
                            <input class="{{ $inputClass }}" name="min_score" type="number" min="0" max="100" value="{{ $gradeScale->min_score }}" required>
                            <input class="{{ $inputClass }}" name="max_score" type="number" min="0" max="100" value="{{ $gradeScale->max_score }}" required>
                        </div>
                        <textarea class="{{ $inputClass }} mt-3" name="description" rows="2">{{ $gradeScale->description }}</textarea>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="{{ $gradeScale->sort_order }}" required>
                        </div>
                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($gradeScale->is_active)>
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
