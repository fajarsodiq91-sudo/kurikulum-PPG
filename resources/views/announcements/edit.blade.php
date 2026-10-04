@extends('layouts.app')

@section('title', 'Edit Berita')
@section('page-title', 'Edit Berita')
@section('breadcrumb', 'Berita / Edit Berita')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Berita</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Edit Berita</h2>
        </div>
        <a href="{{ route('announcements.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali</a>
    </div>

    @include('layouts.partials.validation-errors')

    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('announcements.update', $announcement) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('announcements.partials.fields')

            <button type="submit" class="mt-2 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan Perubahan</button>
        </form>

        <form method="POST" action="{{ route('announcements.destroy', $announcement) }}" class="mt-4 border-t border-slate-100 pt-4" onsubmit="return confirm(@js('Hapus berita '.$announcement->title.'? Tindakan ini tidak dapat dibatalkan.'))">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Hapus Berita</button>
        </form>
    </section>
@endsection
