@extends('layouts.app')

@section('title', 'Berita')
@section('page-title', 'Berita')
@section('breadcrumb', 'Berita')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Berita</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Berita &amp; Pengumuman</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola berita yang tampil di halaman dashboard.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @include('layouts.partials.validation-errors')

    <div class="grid gap-6 lg:grid-cols-[minmax(20rem,0.9fr)_minmax(0,1.5fr)]">
        @if (\App\Support\Access::can('manage-announcements'))
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
                <h3 class="text-lg font-semibold text-slate-950">Tulis Berita</h3>
                <form method="POST" action="{{ route('announcements.store') }}" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    @include('announcements.partials.fields')
                    <button type="submit" class="mt-2 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Publikasikan</button>
                </form>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
            <h3 class="text-lg font-semibold text-slate-950">Daftar Berita</h3>
            <div class="mt-4 space-y-4">
                @forelse ($announcements as $announcement)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $announcement->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $templates[$announcement->template] ?? $announcement->template }} ·
                                    {{ $announcement->status === 'published' ? 'Terbit' : 'Draft' }} ·
                                    {{ $announcement->author?->name ?? 'Sistem' }}
                                </p>
                            </div>
                            @if (\App\Support\Access::can('manage-announcements'))
                                <a href="{{ route('announcements.edit', $announcement) }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-900">Edit</a>
                            @endif
                        </div>
                        @if ($announcement->image)
                            <img src="{{ $announcement->imageUrl() }}" alt="{{ $announcement->title }}" class="mt-3 h-32 w-full rounded-lg object-cover">
                        @endif
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($announcement->body, 200) }}</p>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                        <p class="text-sm font-semibold text-slate-700">Belum ada berita</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-4">{{ $announcements->links() }}</div>
        </div>
    </div>
@endsection
