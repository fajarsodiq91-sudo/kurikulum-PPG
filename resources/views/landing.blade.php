<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ppg-karawang-timur.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-slate-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-9 w-9 object-contain">
                <div>
                    <p class="text-xs font-bold leading-tight tracking-wide text-slate-900">PPG Bidang Pendidikan</p>
                    <p class="text-[11px] leading-tight text-slate-500">Karawang Timur</p>
                </div>
            </div>
            <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                Masuk
            </a>
        </div>
    </header>

    <main>
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2">
                <div>
                    <h1 class="text-3xl font-bold leading-tight text-slate-900 sm:text-4xl lg:text-5xl">
                        Sistem Kurikulum &amp; Pembinaan
                        <span class="text-brand-600">PPG Karawang Timur</span>
                    </h1>
                    <p class="mt-4 text-base leading-relaxed text-slate-600 sm:text-lg">
                        Satu platform terpadu untuk mengelola data generus, kurikulum, kegiatan pembinaan,
                        evaluasi, hingga pelaporan — digunakan oleh guru, pengurus, dan orang tua/wali.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                            Masuk ke Sistem
                        </a>
                        <a href="#fitur" class="inline-flex items-center rounded-md border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">
                            Pelajari Fitur
                        </a>
                    </div>
                </div>
                <div class="flex justify-center">
                    <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-48 w-48 object-contain sm:h-64 sm:w-64">
                </div>
            </div>
        </section>

        <section id="fitur" class="border-t border-slate-200 bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-center text-2xl font-bold text-slate-900 sm:text-3xl">Yang Bisa Dilakukan</h2>
                <p class="mx-auto mt-3 max-w-2xl text-center text-slate-600">
                    Fitur-fitur utama yang mendukung kegiatan pembinaan generus secara menyeluruh.
                </p>

                <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['title' => 'Data Generus', 'desc' => 'Kelola data generus, kelompok, jenjang, dan kelas secara terpusat.'],
                        ['title' => 'Kurikulum & Materi', 'desc' => 'Susun kategori, bab, dan materi pembinaan sesuai jenjang.'],
                        ['title' => 'Kegiatan Pembinaan', 'desc' => 'Atur jadwal, sesi KBM, dan absensi kegiatan harian.'],
                        ['title' => 'Evaluasi & Ujian', 'desc' => 'Catat nilai evaluasi, munaqosah, dan cetak rapor generus.'],
                        ['title' => 'Kontrol Harian', 'desc' => 'Pantau bacaan, hafalan, akhlaq, dan kemandirian generus.'],
                        ['title' => 'Komunikasi & Laporan', 'desc' => 'Sampaikan berita, pesan, dan laporan ke orang tua/wali.'],
                    ] as $feature)
                        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-t border-slate-200 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">Siap Memulai?</h2>
                <p class="mt-3 text-slate-600">Masuk menggunakan akun yang telah diberikan oleh pengurus untuk mengakses sistem.</p>
                <a href="{{ route('login') }}" class="mt-6 inline-flex items-center rounded-md bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                    Masuk ke Sistem
                </a>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-slate-50 py-6">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} PPG Bidang Pendidikan Karawang Timur.</p>
            <a href="https://alfajarlogic.com/" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 transition hover:text-slate-700">
                <img src="{{ asset('images/alfajarlogic-logo.png') }}" alt="Alfajarlogic" class="h-6 w-auto">
                <span>developed by alfajarlogic.com</span>
            </a>
        </div>
    </footer>
</body>
</html>
