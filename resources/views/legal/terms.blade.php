<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syarat dan Ketentuan | PPG</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ppg-karawang-timur.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100">
    <div class="min-h-screen px-4 py-10">
        <div class="mx-auto w-full max-w-3xl bg-white rounded-xl shadow-lg p-8">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-12 w-12 object-contain">
                <div>
                    <p class="text-sm font-bold text-slate-800">PPG Karawang Timur</p>
                    <p class="text-xs text-slate-500">Bidang Pendidikan</p>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-2">Syarat dan Ketentuan</h1>
            <p class="text-sm text-slate-500 mb-8">Terakhir diperbarui: {{ now()->translatedFormat('d F Y') }}</p>

            <div class="space-y-6 text-sm leading-relaxed text-slate-700">
                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">1. Penerimaan Ketentuan</h2>
                    <p>Dengan mengakses dan menggunakan Sistem Kurikulum PPG ("Aplikasi"), Anda menyetujui syarat dan ketentuan ini. Aplikasi ini disediakan untuk keperluan internal pengelolaan kegiatan belajar mengajar (KBM) di lingkungan PPG Karawang Timur.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">2. Kelayakan Pengguna</h2>
                    <p>Aplikasi ini diperuntukkan bagi pengurus, guru, generus, orang tua/wali, dan pihak lain yang terkait dengan kegiatan Bidang Pendidikan PPG Karawang Timur. Pendaftaran akun baru dapat memerlukan persetujuan admin sebelum akun dapat digunakan sepenuhnya.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">3. Akun dan Keamanan</h2>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Anda bertanggung jawab menjaga kerahasiaan kata sandi dan akses akun Anda.</li>
                        <li>Masuk dengan akun Google hanya diperbolehkan menggunakan alamat email yang telah terdaftar dan disetujui dalam sistem.</li>
                        <li>Segera laporkan kepada pengurus Bidang Pendidikan jika Anda menduga akun Anda digunakan tanpa izin.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">4. Penggunaan yang Diperbolehkan</h2>
                    <p>Aplikasi hanya boleh digunakan untuk keperluan administrasi dan kegiatan belajar mengajar yang sah di lingkungan PPG Karawang Timur. Dilarang menyalahgunakan data, mengakses data milik pengguna lain tanpa wewenang, atau mengganggu jalannya sistem.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">5. Data Pengguna</h2>
                    <p>Penggunaan data pribadi dalam Aplikasi tunduk pada <a href="{{ route('legal.privacy') }}" class="text-brand-600 hover:underline">Kebijakan Privasi</a> kami.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">6. Perubahan Layanan</h2>
                    <p>Pengurus Bidang Pendidikan dapat mengubah, menambah, atau menghentikan sebagian fitur Aplikasi sewaktu-waktu demi perbaikan layanan, dengan atau tanpa pemberitahuan sebelumnya.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">7. Kontak</h2>
                    <p>Untuk pertanyaan mengenai syarat dan ketentuan ini, hubungi: <a href="mailto:ptbimtnsc.ptbi@gmail.com" class="text-brand-600 hover:underline">ptbimtnsc.ptbi@gmail.com</a>.</p>
                </section>
            </div>

            <div class="mt-8">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-600 hover:underline">&larr; Kembali ke halaman masuk</a>
            </div>
        </div>
    </div>
</body>
</html>
