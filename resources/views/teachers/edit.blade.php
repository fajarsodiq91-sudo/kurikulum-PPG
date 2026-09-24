@extends('layouts.app')

@section('title', 'Edit Guru')
@section('page-title', 'Edit Guru')
@section('breadcrumb', 'Guru / Edit Guru')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Guru</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Edit Guru</h2>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('teachers.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke data</a>
            <a href="{{ route('teachers.id-card', $teacher) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">ID Card</a>
        </div>
    </div>

    @include('layouts.partials.validation-errors')

    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('teachers.update', $teacher) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('teachers.partials.fields')

            <button type="submit" class="mt-6 w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Simpan Perubahan</button>
        </form>

        <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" class="mt-4 border-t border-slate-100 pt-4" onsubmit="return confirm(@js('Hapus '.$teacher->name.'? Tindakan ini tidak dapat dibatalkan.'))">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Hapus Guru</button>
        </form>
    </section>
@endsection
