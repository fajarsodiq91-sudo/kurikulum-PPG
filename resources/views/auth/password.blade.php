@extends('layouts.app')

@section('title', 'Ganti Password')
@section('page-title', 'Ganti Password')
@section('breadcrumb', 'Akun / Ganti Password')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Akun</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Ganti Password</h2>
        <p class="mt-2 text-sm text-slate-500">Password awal akun generus dan guru sama dengan nomor induk. Sebaiknya segera diganti.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @include('layouts.partials.validation-errors')

    <section class="max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            @foreach ([['current_password', 'Password Saat Ini', 'current-password'], ['password', 'Password Baru', 'new-password'], ['password_confirmation', 'Ulangi Password Baru', 'new-password']] as [$field, $label, $autocomplete])
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="{{ $field }}">{{ $label }} <span class="text-rose-500">*</span></label>
                    <input id="{{ $field }}" name="{{ $field }}" type="password" required autocomplete="{{ $autocomplete }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                </div>
            @endforeach
            <button type="submit" class="w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan Password</button>
        </form>
    </section>
@endsection
