@extends('layouts.app')

@section('title', 'Konfirmasi Kenaikan Kelas')
@section('page-title', 'Konfirmasi Kenaikan Kelas')
@section('breadcrumb', 'Rapor / Konfirmasi Kelompok')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Pelaksana Kelompok</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Konfirmasi Kenaikan Kelas</h2>
        <p class="mt-2 text-sm text-slate-500">PPG Daerah telah mengonfirmasi penerbitan rapor semester ini. Tandai generus yang tinggal kelas di kelompok Anda sebelum rapor terbit — generus yang tidak ditandai otomatis naik kelas.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($confirmations as $confirmation)
                <div class="flex items-center justify-between gap-3 py-4">
                    <div>
                        <span class="font-semibold text-slate-900">{{ $confirmation->publication->semester->name }} {{ $confirmation->publication->academicYear->name }}</span>
                        <span class="ml-2 text-sm text-slate-500">{{ $confirmation->group->name }}</span>
                    </div>

                    @if ($confirmation->isPending())
                        <a href="{{ route('report-card-group-confirmations.edit', $confirmation) }}" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Konfirmasi</a>
                    @else
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Sudah Dikonfirmasi</span>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">Belum ada penerbitan rapor yang perlu dikonfirmasi.</p>
            @endforelse
        </div>
    </section>
@endsection
