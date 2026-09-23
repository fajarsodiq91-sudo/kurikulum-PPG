@extends('layouts.app')

@section('title', 'Tambah Generus')
@section('page-title', 'Tambah Generus')
@section('breadcrumb', 'Generus / Tambah Generus')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Generus</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Tambah Generus</h2>
            <p class="mt-2 text-sm text-slate-500">Lengkapi identitas dan penempatan awal generus.</p>
        </div>
        <a href="{{ route('generus.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke data</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">Periksa kembali isian form.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('generus.partials.form', [
        'action' => route('generus.store'),
        'generus' => null,
        'currentAssignment' => null,
        'registrationNumber' => $generatedRegistrationNumber,
        'recordNumber' => $generatedRecordNumber,
        'nis' => $generatedRegistrationNumber,
    ])
@endsection
