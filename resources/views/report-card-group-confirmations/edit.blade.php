@extends('layouts.app')

@section('title', 'Konfirmasi Kenaikan Kelas')
@section('page-title', 'Konfirmasi Kenaikan Kelas')
@section('breadcrumb', 'Rapor / Konfirmasi Kelompok')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">{{ $confirmation->group->name }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $confirmation->publication->semester->name }} {{ $confirmation->publication->academicYear->name }}</h2>
        <p class="mt-2 text-sm text-slate-500">Centang generus yang <strong>tinggal kelas</strong>. Generus yang tidak dicentang akan otomatis naik ke kelas KBM berikutnya begitu Anda simpan, dan tidak bisa diubah lagi setelahnya.</p>
    </div>

    @if (! $confirmation->isPending())
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">Konfirmasi ini sudah selesai diproses.</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('report-card-group-confirmations.update', $confirmation) }}">
            @csrf
            @method('PUT')

            <ul class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                @forelse ($roster as $generus)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <span>
                            <span class="font-medium text-slate-800">{{ $generus->full_name }}</span>
                            <span class="ml-2 text-xs text-slate-400">{{ $generus->learningClass?->name ?? 'Belum ada kelas KBM' }}</span>
                        </span>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                name="retained_generus_ids[]"
                                value="{{ $generus->id }}"
                                @checked($confirmation->retainedGenerus->contains('id', $generus->id))
                                @disabled(! $confirmation->isPending())
                                class="h-4 w-4 rounded border-slate-300"
                            >
                            Tinggal Kelas
                        </label>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-slate-500">Tidak ada generus aktif di kelompok ini.</li>
                @endforelse
            </ul>

            @if ($confirmation->isPending())
                <button type="submit" class="mt-6 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 sm:w-auto">Simpan Konfirmasi</button>
            @endif
        </form>
    </section>
@endsection
