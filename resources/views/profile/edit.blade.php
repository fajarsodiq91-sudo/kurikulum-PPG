@extends('layouts.app')

@section('title', 'Edit Profil')
@section('page-title', 'Edit Profil')
@section('breadcrumb', 'Akun / Edit Profil')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Akun</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Edit Profil</h2>
        <p class="mt-2 text-sm text-slate-500">Lengkapi foto dan nomor WhatsApp agar mudah dihubungi pengurus lain lewat bagan struktur organisasi.</p>
    </div>

    <section class="max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-4">
                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-full bg-brand-100">
                    @if ($user->photo)
                        <img src="{{ route('users.photo', $user) }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-xl font-bold text-brand-700">{{ str($user->name)->substr(0, 1)->upper() }}</div>
                    @endif
                </div>
                <div class="flex-1">
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="photo">Foto Profil</label>
                    <input id="photo" name="photo" type="file" accept="image/png,image/jpeg,image/webp" class="w-full text-sm">
                    @error('photo')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Nama <span class="text-rose-500">*</span></label>
                <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="whatsapp">Nomor WhatsApp</label>
                <input id="whatsapp" name="whatsapp" type="text" placeholder="08xxxxxxxxxx" value="{{ old('whatsapp', $user->whatsapp) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                <p class="mt-1 text-xs text-slate-500">Dipakai sebagai tombol WhatsApp pada bagan struktur organisasi.</p>
                @error('whatsapp')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-500">{{ $user->email ?? '-' }}</p>
            </div>

            <button type="submit" class="w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan Profil</button>
        </form>
    </section>
@endsection
