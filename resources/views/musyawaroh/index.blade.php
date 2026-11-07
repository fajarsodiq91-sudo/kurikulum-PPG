@extends('layouts.app')

@section('title', 'Musyawaroh')
@section('page-title', 'Musyawaroh')
@section('breadcrumb', 'Musyawaroh')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Pengurus</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Daftar Musyawaroh</h2>
            <p class="mt-2 text-sm text-slate-500">Daftar hadir dan notulen setiap musyawaroh, sesuai hirarki kepengurusan.</p>
        </div>
        @if (\App\Support\Access::can('manage-tasks'))
            <a href="{{ route('musyawaroh.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                + Buat Musyawaroh
            </a>
        @endif
    </div>

    @if ($musyawarohs->isEmpty())
        <x-empty-state title="Belum ada musyawaroh." description="Buat musyawaroh untuk mencatat daftar hadir dan notulen." class="mb-6" />
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($musyawarohs as $musyawaroh)
                @php
                    $doneCount = $musyawaroh->notulens->where('is_done', true)->count();
                    $totalNotulen = $musyawaroh->notulens->count();
                @endphp
                <a href="{{ route('musyawaroh.show', $musyawaroh) }}" class="block">
                    <x-card class="h-full transition hover:border-brand-300 hover:shadow-md">
                        <p class="font-semibold text-slate-900">{{ $musyawaroh->title ?: 'Musyawaroh' }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $musyawaroh->meeting_date->translatedFormat('d M Y') }}
                            @if ($musyawaroh->start_time)
                                &middot; {{ \Illuminate\Support\Carbon::parse($musyawaroh->start_time)->format('H:i') }}@if ($musyawaroh->end_time)-{{ \Illuminate\Support\Carbon::parse($musyawaroh->end_time)->format('H:i') }}@endif
                            @endif
                            @if ($musyawaroh->location)
                                &middot; {{ $musyawaroh->location }}
                            @endif
                        </p>
                        <div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
                            <span>{{ $musyawaroh->attendees->count() }} hadir</span>
                            <span>Notulen {{ $doneCount }}/{{ $totalNotulen }}</span>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">oleh {{ $musyawaroh->createdBy?->name ?? '-' }}</p>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
@endsection
