@extends('layouts.app')

@section('title', 'Data Generus')
@section('page-title', 'Data Generus')
@section('breadcrumb', 'Generus / Data Generus')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Generus</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Data Generus</h2>
            <p class="mt-2 text-sm text-slate-500">Kelola identitas dan riwayat penempatan generus.</p>
        </div>
        @if (\App\Support\Access::can('manage-generus'))
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('generus.export') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Export XLSX</a>
                <a href="{{ route('generus.create') }}" class="inline-flex items-center justify-center rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Tambah Generus</a>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">Import XLSX gagal.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (\App\Support\Access::can('manage-generus'))
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Import Database Generus</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan file XLSX hasil export aplikasi ini agar kode master wilayah dapat dipetakan dengan benar.</p>
            <form method="POST" action="{{ route('generus.import') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="file">File XLSX</label>
                    <input id="file" name="file" type="file" accept=".xlsx" required class="block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-600">Import XLSX</button>
            </form>
        </section>
    @endif

    <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-950">Daftar Generus</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $generus->total() }} data terdaftar.</p>
            </div>
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="py-3 pr-4 font-semibold">Nomor Induk</th>
                        <th class="py-3 pr-4 font-semibold">Nama</th>
                        <th class="py-3 pr-4 font-semibold">Status</th>
                        <th class="py-3 pr-4 font-semibold">Penempatan</th>
                        <th class="py-3 font-semibold"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($generus as $item)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-3 pr-4">{{ $item->registration_number }}</td>
                            <td class="py-3 pr-4"><a href="{{ route('generus.show', $item) }}" class="font-medium text-slate-900 hover:text-amber-600">{{ $item->full_name }}</a></td>
                            <td class="py-3 pr-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($item->status === 'pindah_sambung' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ match ($item->status) { 'active' => 'Aktif', 'pindah_sambung' => 'Pindah Sambung', 'married' => 'Sudah Menikah', default => $item->status } }}</span></td>
                            <td class="py-3 pr-4">
                                @foreach($item->assignments as $assignment)
                                    <div class="text-xs leading-6 text-slate-600">
                                        {{ $assignment->region?->name ?? '-' }} / {{ $assignment->village?->name ?? '-' }} / {{ $assignment->group?->name ?? '-' }}
                                    </div>
                                @endforeach
                                @if ($item->assignments->isEmpty())
                                    <span class="text-xs text-slate-400">Belum ditempatkan</span>
                                @endif
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <x-action-icon :href="route('generus.show', $item)" label="Detail" color="slate"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></x-action-icon>
                                @if (\App\Support\Access::can('manage-generus'))
                                    <x-action-icon :href="route('generus.edit', $item)" label="Edit" color="amber"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>
                                    <x-action-icon :href="route('generus.id-card', $item)" label="ID Card" color="slate"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0ZM3.75 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109" /></svg></x-action-icon>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center"><p class="font-semibold text-slate-700">Belum ada data generus</p><p class="mt-1 text-sm text-slate-500">Tambahkan generus pertama untuk mulai mengelola pembinaan.</p></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $generus->links() }}</div>
    </section>
@endsection
