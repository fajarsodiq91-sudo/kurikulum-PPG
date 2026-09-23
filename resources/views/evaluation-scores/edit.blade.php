@extends('layouts.app')

@section('title', 'Edit Skor Evaluasi')
@section('page-title', 'Edit Skor Evaluasi')
@section('breadcrumb', 'Evaluasi / Edit Skor Evaluasi')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Evaluasi</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Edit Skor Evaluasi</h2>
        </div>
        <a href="{{ route('evaluation-scores.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke data</a>
    </div>

    @include('layouts.partials.validation-errors')

    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('evaluation-scores.update', $score) }}">
            @csrf
            @method('PUT')
            @include('evaluation-scores.partials.fields')

            <button type="submit" class="mt-6 w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Simpan Perubahan</button>
        </form>

        <form method="POST" action="{{ route('evaluation-scores.destroy', $score) }}" class="mt-4 border-t border-slate-100 pt-4" onsubmit="return confirm(@js('Hapus '.'skor '.($score->generus?->full_name ?? '').' pada '.($score->evaluation?->title ?? '').'? Tindakan ini tidak dapat dibatalkan.'))">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Hapus Skor Evaluasi</button>
        </form>
    </section>
@endsection
