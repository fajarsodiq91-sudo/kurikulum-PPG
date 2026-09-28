@extends('layouts.app')

@section('title', 'Data Wajah')
@section('page-title', 'Data Wajah')
@section('breadcrumb', 'Manajemen Pengguna / Data Wajah')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Manajemen Pengguna</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Data Wajah</h2>
        <p class="mt-2 text-sm text-slate-500">Data wajah dipakai untuk login wajah. Foto yang diunggah lewat form generus atau guru diproses otomatis. Halaman ini melengkapi foto lama yang belum diproses.</p>
    </div>

    <section class="max-w-2xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        data-face-backfill
        data-model-url="{{ asset('models/face-api') }}"
        data-items='@json($pending)'>
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Sudah diproses</dt><dd class="text-2xl font-bold text-slate-950">{{ $enrolled }}</dd></div>
            <div><dt class="text-slate-500">Belum diproses</dt><dd class="text-2xl font-bold text-slate-950" data-face-remaining>{{ $pending->count() }}</dd></div>
        </dl>
        <button type="button" data-face-start @disabled($pending->isEmpty()) class="mt-6 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-50">Proses Foto Sekarang</button>
        <p class="mt-4 text-sm text-slate-600" data-face-progress role="status" aria-live="polite"></p>
        <p class="mt-4 text-xs text-slate-500">Biarkan halaman ini terbuka sampai selesai. Foto yang wajahnya tidak terdeteksi perlu diganti dengan foto yang lebih jelas.</p>
    </section>
    @vite('resources/js/face-backfill.js')
@endsection
