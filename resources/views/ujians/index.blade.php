@extends('layouts.app')

@section('title', 'Ujian')
@section('page-title', 'Ujian')
@section('breadcrumb', 'Ujian / Daftar Ujian')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Ujian</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Ujian</h2>
            <p class="mt-2 text-sm text-slate-500">Kelola ulangan harian, mingguan, dan bulanan generus. Nilai rapor otomatis dihitung dari rata-rata nilai ujian ini dengan munaqosah pada semester yang sama.</p>
        </div>
        @if (\App\Support\Access::can('manage-ujian'))
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('ujians.batch') }}" class="inline-flex items-center justify-center rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Input Nilai per Kelompok</a>
            </div>
        @endif
    </div>

        @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('ujians.export'), 'importUrl' => \App\Support\Access::can('manage-ujian') ? route('ujians.import') : null])

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            @if (\App\Support\Access::can('manage-ujian'))
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Ujian</h2>
                <form method="POST" action="{{ route('ujians.store') }}">
                    @csrf
                    @include('ujians.partials.fields', ['ujian' => null])
                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </form>
            </div>
            @endif

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Ujian</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Generus</th>
                                <th class="py-3 pr-4">Judul</th>
                                <th class="py-3 pr-4">Tipe</th>
                                <th class="py-3 pr-4">Nilai</th>
                                <th class="py-3 pr-4">Hasil</th>
                                <th class="py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ujians as $ujian)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $ujian->generus?->full_name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $ujian->title }}</td>
                                    <td class="py-3 pr-4 capitalize">{{ $ujian->type }}</td>
                                    <td class="py-3 pr-4">{{ $ujian->score ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $ujian->result ?? '-' }}</td>
                                    <td class="py-3 text-right">@if (\App\Support\Access::can('manage-ujian'))<x-action-icon :href="route('ujians.edit', $ujian)" label="Edit" color="amber"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>@endif</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-500">Belum ada ujian.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $ujians->links() }}</div>
            </div>
        </div>
    </div>
@endsection
