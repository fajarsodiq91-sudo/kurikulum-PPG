@extends('layouts.app')

@section('title', 'Laporan')
@section('page-title', 'Laporan')
@section('breadcrumb', 'Laporan')

@section('content')
    <div class="mb-8">
        <p class="text-sm font-semibold text-amber-600">Laporan</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Ringkasan Program PPG</h2>
        <p class="mt-2 text-sm text-slate-500">Pantau ringkasan capaian dan jadwal pelatihan terbaru.</p>
    </div>

            <div class="mb-8 grid gap-4 md:grid-cols-3">
                <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-500">Generus</p>
                    <p class="mt-2 text-3xl font-bold">{{ $generusCount }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-500">Guru</p>
                    <p class="mt-2 text-3xl font-bold">{{ $teacherCount }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-500">Pelatihan</p>
                    <p class="mt-2 text-3xl font-bold">{{ $trainingCount }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Jadwal Pelatihan Terbaru</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Judul</th>
                                <th class="py-3 pr-4">Tahun</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trainings as $training)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $training->title }}</td>
                                    <td class="py-3 pr-4">{{ $training->academicYear?->name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $training->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada data pelatihan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
@endsection
