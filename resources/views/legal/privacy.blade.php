<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kebijakan Privasi | PPG</title>
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

            <h1 class="text-2xl font-bold text-slate-800 mb-2">Kebijakan Privasi</h1>
            <p class="text-sm text-slate-500 mb-8">Terakhir diperbarui: {{ now()->translatedFormat('d F Y') }}</p>

            <div class="space-y-6 text-sm leading-relaxed text-slate-700">
                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">1. Tentang Aplikasi Ini</h2>
                    <p>Sistem Kurikulum PPG ("Aplikasi") digunakan oleh Bidang Pendidikan PPG Karawang Timur untuk mengelola kegiatan belajar mengajar (KBM), data generus, kehadiran, dan administrasi terkait lainnya. Kebijakan ini menjelaskan data apa saja yang kami kumpulkan, bagaimana data tersebut digunakan, dan bagaimana kami melindunginya.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">2. Data yang Kami Kumpulkan</h2>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><span class="font-medium">Data akun</span> — nama, alamat email, dan nama pengguna saat Anda mendaftar atau masuk, termasuk saat masuk menggunakan akun Google (nama dan email dari profil Google Anda).</li>
                        <li><span class="font-medium">Data keanggotaan</span> — data generus, penempatan kelompok/desa, peran (role), dan data terkait proses belajar seperti tugas, penilaian, dan catatan kemajuan.</li>
                        <li><span class="font-medium">Data kehadiran</span> — catatan kehadiran pada sesi KBM, termasuk metode pencatatan seperti kartu RFID, QR, atau pengenalan wajah.</li>
                        <li><span class="font-medium">Data biometrik (opsional)</span> — jika Anda mendaftarkan fitur absensi wajah, kami menyimpan representasi numerik wajah (face descriptor) yang dihasilkan di perangkat Anda, bukan foto wajah itu sendiri. Data ini hanya digunakan untuk mencocokkan kehadiran dan tidak dibagikan ke pihak ketiga.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">3. Masuk dengan Google</h2>
                    <p>Saat Anda memilih "Masuk dengan Google", Aplikasi hanya meminta akses ke nama dan alamat email akun Google Anda untuk mencocokkan dan membuat akun pada sistem kami. Kami tidak mengakses Gmail, Google Drive, kontak, atau data Google lain di luar itu, dan kami tidak menyimpan kata sandi akun Google Anda.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">4. Bagaimana Data Digunakan</h2>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Mengelola akses masuk (login) dan hak akses sesuai peran pengguna.</li>
                        <li>Mencatat dan melaporkan kehadiran serta perkembangan belajar generus.</li>
                        <li>Komunikasi administratif terkait kegiatan KBM antara pengurus, guru, dan orang tua/wali.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">5. Penyimpanan dan Keamanan</h2>
                    <p>Data disimpan pada server yang dikelola oleh pengurus Bidang Pendidikan PPG Karawang Timur dan hanya dapat diakses oleh pengguna dengan peran yang berwenang. Kami tidak menjual atau membagikan data pribadi Anda kepada pihak ketiga untuk tujuan komersial.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">6. Hak Anda</h2>
                    <p>Anda dapat meminta koreksi atau penghapusan data pribadi Anda dengan menghubungi pengurus Bidang Pendidikan melalui kontak di bawah ini.</p>
                </section>

                <section>
                    <h2 class="text-base font-semibold text-slate-800 mb-2">7. Kontak</h2>
                    <p>Untuk pertanyaan mengenai kebijakan privasi ini, hubungi: <a href="mailto:ptbimtnsc.ptbi@gmail.com" class="text-brand-600 hover:underline">ptbimtnsc.ptbi@gmail.com</a>.</p>
                </section>
            </div>

            <div class="mt-8">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-brand-600 hover:underline">&larr; Kembali ke halaman masuk</a>
            </div>
        </div>
    </div>
</body>
</html>
