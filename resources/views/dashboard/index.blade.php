@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Utama')
@section('breadcrumb', 'Dashboard Utama')

@section('content')
    @if ($announcements->isNotEmpty())
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-bold text-slate-950">Berita &amp; Pengumuman</h2>
                @if (\App\Support\Access::can('manage-announcements'))
                    <a href="{{ route('announcements.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Kelola berita</a>
                @endif
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($announcements as $announcement)
                    <article class="rounded-xl border border-slate-200 p-4 {{ $announcement->template === 'highlight' ? 'bg-amber-50 border-amber-200' : '' }}">
                        @if ($announcement->image && $announcement->template !== 'image-left')
                            <img src="{{ $announcement->imageUrl() }}" alt="{{ $announcement->title }}" class="mb-3 h-32 w-full rounded-lg object-cover">
                        @endif
                        <div class="flex gap-3">
                            @if ($announcement->image && $announcement->template === 'image-left')
                                <img src="{{ $announcement->imageUrl() }}" alt="{{ $announcement->title }}" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                            @endif
                            <div>
                                <p class="font-semibold text-slate-900">{{ $announcement->title }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $announcement->published_at?->translatedFormat('d M Y') }} · {{ $announcement->author?->name ?? 'Sistem' }}</p>
                            </div>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($announcement->body, 160) }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @guest
        <div class="mb-6 flex flex-col justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center">
            <p class="text-sm text-amber-800">Anda melihat dashboard sebagai <strong>Tamu</strong> dengan akses baca-saja. Masuk untuk mengelola data sesuai peran Anda.</p>
            <a href="{{ route('login') }}" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-800">Masuk</a>
        </div>
    @endguest

    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Ringkasan sistem</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Selamat datang, {{ auth()->user()->name ?? 'Tamu' }}</h2>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Pantau data pembinaan dan aktivitas PPG Karawang Timur dari satu tempat.</p>
        </div>
        @if (\App\Support\Access::can('view-reports'))
            <a href="{{ route('reports.index') }}" class="inline-flex items-center justify-center rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Buka laporan</a>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Generus Aktif', 'value' => $generusCount, 'badge' => 'bg-brand-50 text-brand-600', 'icon' => 'group'],
            ['label' => 'Guru Aktif', 'value' => $teacherCount, 'badge' => 'bg-brand-50 text-brand-600', 'icon' => 'person'],
            ['label' => 'Desa', 'value' => $regionCount, 'badge' => 'bg-emerald-50 text-emerald-600', 'icon' => 'map-pin'],
            ['label' => 'Kelompok', 'value' => $groupCount, 'badge' => 'bg-emerald-50 text-emerald-600', 'icon' => 'squares'],
            ['label' => 'Sesi KBM', 'value' => $learningSessionCount, 'badge' => 'bg-amber-50 text-amber-600', 'icon' => 'book-open'],
            ['label' => 'Munaqosah', 'value' => $munaqosahCount, 'badge' => 'bg-amber-50 text-amber-600', 'icon' => 'check-badge'],
            ['label' => 'Pelatihan Aktif', 'value' => $trainingCount, 'badge' => 'bg-amber-50 text-amber-600', 'icon' => 'bolt'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $card['badge'] }}">
                    @switch($card['icon'])
                        @case('group')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 19.25a5.5 5.5 0 0 1 11 0"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.5 14.5c2.761 0 5.5 1.5 5.5 4.75"/></svg>
                            @break
                        @case('person')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0 1 15 0"/></svg>
                            @break
                        @case('map-pin')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 6-7.5 11.25-7.5 11.25S4.5 16.5 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            @break
                        @case('squares')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6a2.25 2.25 0 0 1 2.25-2.25h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6a2.25 2.25 0 0 1 2.25-2.25h2.25A2.25 2.25 0 0 1 20.25 6v2.25a2.25 2.25 0 0 1-2.25 2.25h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 16.5a2.25 2.25 0 0 1 2.25-2.25h2.25a2.25 2.25 0 0 1 2.25 2.25v2.25A2.25 2.25 0 0 1 8.25 21H6a2.25 2.25 0 0 1-2.25-2.25V16.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.5a2.25 2.25 0 0 1 2.25-2.25h2.25a2.25 2.25 0 0 1 2.25 2.25v2.25A2.25 2.25 0 0 1 18 21h-2.25a2.25 2.25 0 0 1-2.25-2.25V16.5Z"/></svg>
                            @break
                        @case('book-open')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                            @break
                        @case('check-badge')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/></svg>
                            @break
                        @case('bolt')
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 14.25 2.25 12 10.5h8.25L9.75 21.75 12 13.5H3.75Z"/></svg>
                            @break
                    @endswitch
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-950">{{ $card['value'] }}</p>
                <p class="mt-1 text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-bold text-slate-950">Akses cepat</h2>
                    <p class="mt-1 text-sm text-slate-500">Buka modul yang paling sering digunakan.</p>
                </div>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @if (\App\Support\Access::can('manage-generus'))
                    <a href="{{ route('generus.index') }}" class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50">
                        <svg class="h-5 w-5 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 19.25a5.5 5.5 0 0 1 11 0"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.5 14.5c2.761 0 5.5 1.5 5.5 4.75"/></svg>
                        <span><span class="block text-sm font-semibold text-slate-900">Data Generus</span><span class="mt-1 block text-xs text-slate-500">Lihat, import, dan export database generus</span></span>
                    </a>
                    <a href="{{ route('generus.create') }}" class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50">
                        <svg class="h-5 w-5 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 20.25a7.5 7.5 0 0 1 13.5-4.5M19.5 15v3m1.5-1.5h-3"/></svg>
                        <span><span class="block text-sm font-semibold text-slate-900">Tambah Generus</span><span class="mt-1 block text-xs text-slate-500">Daftarkan generus baru</span></span>
                    </a>
                @endif
                @if (\App\Support\Access::can('manage-learning-sessions'))
                    <a href="{{ route('learning-sessions.index') }}" class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50">
                        <svg class="h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                        <span><span class="block text-sm font-semibold text-slate-900">Kelola Sesi KBM</span><span class="mt-1 block text-xs text-slate-500">Lihat dan buat sesi pembelajaran</span></span>
                    </a>
                @endif
                @if (\App\Support\Access::can('manage-evaluations'))
                    <a href="{{ route('evaluations.index') }}" class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50">
                        <svg class="h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75h6a1.5 1.5 0 0 1 1.5 1.5v.75h-9v-.75A1.5 1.5 0 0 1 9 3.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 6h9a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 19.5v-12A1.5 1.5 0 0 1 7.5 6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m9 13.5 2.25 2.25L15 11.25"/></svg>
                        <span><span class="block text-sm font-semibold text-slate-900">Input Evaluasi</span><span class="mt-1 block text-xs text-slate-500">Catat evaluasi pembinaan</span></span>
                    </a>
                @endif
                @if (\App\Support\Access::can('view-reports'))
                    <a href="{{ route('reports.index') }}" class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50">
                        <svg class="h-5 w-5 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 20.25h18M6 20.25v-6M12 20.25v-10.5M18 20.25V6"/></svg>
                        <span><span class="block text-sm font-semibold text-slate-900">Lihat Laporan</span><span class="mt-1 block text-xs text-slate-500">Buka ringkasan pelaporan</span></span>
                    </a>
                @endif
                @if (\App\Support\Access::can('view-master-data'))
                    <a href="{{ route('master-data.index') }}" class="flex items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50">
                        <svg class="h-5 w-5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6a2.25 2.25 0 0 1 2.25-2.25h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6a2.25 2.25 0 0 1 2.25-2.25h2.25A2.25 2.25 0 0 1 20.25 6v2.25a2.25 2.25 0 0 1-2.25 2.25h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 16.5a2.25 2.25 0 0 1 2.25-2.25h2.25a2.25 2.25 0 0 1 2.25 2.25v2.25A2.25 2.25 0 0 1 8.25 21H6a2.25 2.25 0 0 1-2.25-2.25V16.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.5a2.25 2.25 0 0 1 2.25-2.25h2.25a2.25 2.25 0 0 1 2.25 2.25v2.25A2.25 2.25 0 0 1 18 21h-2.25a2.25 2.25 0 0 1-2.25-2.25V16.5Z"/></svg>
                        <span><span class="block text-sm font-semibold text-slate-900">Master Data</span><span class="mt-1 block text-xs text-slate-500">Kelola desa, kelompok, jenjang, kelas, dan konfigurasi dinamis</span></span>
                    </a>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-slate-950">Sesi KBM mendatang</h2>
            @if ($upcomingSessions->isNotEmpty())
                <ul class="mt-5 space-y-3">
                    @foreach ($upcomingSessions as $session)
                        <li class="flex items-start gap-3 rounded-xl border border-slate-200 p-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $session->material?->title ?? 'Sesi KBM' }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $session->session_date }}{{ $session->start_time ? ' · '.$session->start_time : '' }} · {{ $session->levelLabel() }}</p>
                                <p class="mt-0.5 text-xs text-slate-400">{{ $session->teacher?->name ?? 'Guru belum ditentukan' }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                    <p class="text-sm font-semibold text-slate-700">Belum ada sesi terjadwal</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Sesi KBM yang dijadwalkan akan tampil di sini.</p>
                </div>
            @endif
        </section>
    </div>

    @if ($generusCount === 0 && $teacherCount === 0 && $learningSessionCount === 0)
        <section class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6">
            <h2 class="font-bold text-slate-900">Belum ada data operasional</h2>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Dashboard siap digunakan. Mulai dengan menambahkan generus, guru, atau sesi KBM melalui menu yang tersedia.</p>
        </section>
    @endif
@endsection
