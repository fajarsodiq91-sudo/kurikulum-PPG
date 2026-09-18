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
        <a href="{{ route('generus.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Tambah Generus</a>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-950">Daftar Generus</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $generus->count() }} data terdaftar.</p>
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
                    </tr>
                </thead>
                <tbody>
                    @forelse($generus as $item)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-3 pr-4">{{ $item->registration_number }}</td>
                            <td class="py-3 pr-4">{{ $item->full_name }}</td>
                            <td class="py-3 pr-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center"><p class="font-semibold text-slate-700">Belum ada data generus</p><p class="mt-1 text-sm text-slate-500">Tambahkan generus pertama untuk mulai mengelola pembinaan.</p></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
