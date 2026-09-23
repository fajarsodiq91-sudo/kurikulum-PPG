@extends('layouts.app')

@section('title', 'Ubah Peran')
@section('page-title', 'Ubah Peran')
@section('breadcrumb', 'Manajemen Pengguna / Ubah Peran')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Manajemen Pengguna</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Ubah Peran</h2>
        </div>
        <a href="{{ route('roles.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke data</a>
    </div>

    @include('layouts.partials.validation-errors')

    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf
            @method('PUT')
            @include('roles.partials.fields')

            <button type="submit" class="mt-6 w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Simpan Perubahan</button>
        </form>

        <form method="POST" action="{{ route('roles.destroy', $role) }}" class="mt-4 border-t border-slate-100 pt-4" onsubmit="return confirm(@js('Hapus '.$role->name.'? Tindakan ini tidak dapat dibatalkan.'))">
            @csrf
            @method('DELETE')
            <button type="submit" class="w-full rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Hapus Peran</button>
        </form>
    </section>
@endsection
