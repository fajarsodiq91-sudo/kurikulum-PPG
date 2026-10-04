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
            ['label' => 'Generus Aktif', 'value' => $generusCount, 'dot' => 'bg-amber-400'],
            ['label' => 'Guru Aktif', 'value' => $teacherCount, 'dot' => 'bg-brand-500'],
            ['label' => 'Desa / Wilayah', 'value' => $regionCount, 'dot' => 'bg-emerald-500'],
            ['label' => 'Kelompok', 'value' => $groupCount, 'dot' => 'bg-brand-300'],
            ['label' => 'Sesi KBM', 'value' => $learningSessionCount, 'dot' => 'bg-amber-600'],
            ['label' => 'Munaqosah', 'value' => $munaqosahCount, 'dot' => 'bg-emerald-600'],
            ['label' => 'Pelatihan Aktif', 'value' => $trainingCount, 'dot' => 'bg-brand-700'],
        ] as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                    <span class="h-2.5 w-2.5 rounded-full {{ $card['dot'] }}"></span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-950">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">Data aktual database</p>
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
                    <a href="{{ route('generus.index') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50"><span class="text-sm font-semibold text-slate-900">Data Generus</span><span class="mt-1 block text-xs text-slate-500">Lihat, import, dan export database generus</span></a>
                    <a href="{{ route('generus.create') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50"><span class="text-sm font-semibold text-slate-900">Tambah Generus</span><span class="mt-1 block text-xs text-slate-500">Daftarkan generus baru</span></a>
                @endif
                @if (\App\Support\Access::can('manage-learning-sessions'))
                    <a href="{{ route('learning-sessions.index') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50"><span class="text-sm font-semibold text-slate-900">Kelola Sesi KBM</span><span class="mt-1 block text-xs text-slate-500">Lihat dan buat sesi pembelajaran</span></a>
                @endif
                @if (\App\Support\Access::can('manage-evaluations'))
                    <a href="{{ route('evaluations.index') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50"><span class="text-sm font-semibold text-slate-900">Input Evaluasi</span><span class="mt-1 block text-xs text-slate-500">Catat evaluasi pembinaan</span></a>
                @endif
                @if (\App\Support\Access::can('view-reports'))
                    <a href="{{ route('reports.index') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50"><span class="text-sm font-semibold text-slate-900">Lihat Laporan</span><span class="mt-1 block text-xs text-slate-500">Buka ringkasan pelaporan</span></a>
                @endif
                @if (\App\Support\Access::can('view-master-data'))
                    <a href="{{ route('master-data.index') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-amber-300 hover:bg-amber-50"><span class="text-sm font-semibold text-slate-900">Master Data</span><span class="mt-1 block text-xs text-slate-500">Kelola desa, kelompok, jenjang, kelas, dan konfigurasi dinamis</span></a>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-slate-950">Aktivitas terbaru</h2>
            <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                <p class="text-sm font-semibold text-slate-700">Belum ada aktivitas</p>
                <p class="mt-1 text-xs leading-5 text-slate-500">Activity log belum tersedia pada modul saat ini.</p>
            </div>
        </section>
    </div>

    @if ($generusCount === 0 && $teacherCount === 0 && $learningSessionCount === 0)
        <section class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6">
            <h2 class="font-bold text-slate-900">Belum ada data operasional</h2>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Dashboard siap digunakan. Mulai dengan menambahkan generus, guru, atau sesi KBM melalui menu yang tersedia.</p>
        </section>
    @endif
@endsection
