@extends('layouts.app')

@section('title', 'Rapor Generus')
@section('page-title', 'Rapor Generus')
@section('breadcrumb', 'Rapor / Rapor Generus')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Rapor</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Rapor Generus</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola penilaian akhir dan predikat generus.</p>
    </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @include('layouts.partials.validation-errors')

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            @if (\App\Support\Access::can('manage-report-cards'))
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Rapor</h2>
                <form method="POST" action="{{ route('report-cards.store') }}">
                    @csrf
                    @include('report-cards.partials.fields', ['reportCard' => null])

                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </form>
            </div>
            @endif

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Rapor</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Generus</th>
                                <th class="py-3 pr-4">Nilai</th>
                                <th class="py-3 pr-4">Predikat</th>
                                <th class="py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportCards as $card)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $card->generus?->full_name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $card->final_score ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $card->predicate ?? '-' }}</td>
                                    <td class="py-3 text-right">@if (\App\Support\Access::can('manage-report-cards'))<x-action-icon :href="route('report-cards.edit', $card)" label="Edit" color="amber"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>@endif</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-500">Belum ada rapor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $reportCards->links() }}</div>
            </div>
        </div>
    </div>
@endsection
