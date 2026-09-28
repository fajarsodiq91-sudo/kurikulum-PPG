@extends('layouts.app')

@section('title', 'Tutorial')
@section('page-title', 'Tutorial')
@section('breadcrumb', 'Tutorial')

@php
    $topics = [
        [
            'id' => 'mulai',
            'title' => 'Mulai Menggunakan Aplikasi',
            'summary' => 'Cara masuk, mengganti password, dan memahami menu yang tampil.',
            'features' => [
                ['title' => 'Masuk ke aplikasi', 'steps' => [
                    'Buka halaman login, lalu isi kolom "Email atau Nomor Induk".',
                    'Admin dan pengurus memakai email. Generus dan guru memakai nomor induknya sendiri.',
                    'Password awal generus dan guru sama dengan nomor induk. Setelah itu klik "Masuk".',
                ]],
                ['title' => 'Mengganti password', 'steps' => [
                    'Klik lingkaran nama Anda di pojok kanan atas, lalu pilih "Ganti password".',
                    'Isi password saat ini, lalu password baru (minimal 8 karakter) dua kali dan klik "Simpan Password".',
                    'Sangat disarankan mengganti password awal yang sama dengan nomor induk.',
                ]],
                ['title' => 'Keluar dari aplikasi', 'steps' => [
                    'Klik lingkaran nama di pojok kanan atas, lalu pilih "Keluar dari sistem".',
                ]],
                ['title' => 'Mengapa menu saya berbeda dengan orang lain?', 'steps' => [
                    'Menu mengikuti peran akun Anda. Menu yang tidak tersedia untuk peran Anda tidak ditampilkan.',
                    'Peran "baca-saja" (misalnya PPG dan Perwakilan PPG Desa) hanya bisa melihat data, sehingga tombol tambah, edit, dan import tidak muncul.',
                    'Peran wilayah (desa atau kelompok) hanya melihat data di desa atau kelompoknya.',
                    'Tanpa login (tamu), Anda hanya bisa melihat sebagian data secara baca-saja.',
                ]],
            ],
        ],
        [
            'id' => 'dashboard',
            'title' => 'Dashboard',
            'permission' => ['view-dashboard'],
            'summary' => 'Ringkasan jumlah generus, guru, dan kegiatan.',
            'features' => [
                ['title' => 'Membaca dashboard', 'steps' => [
                    'Klik "Dashboard" di menu kiri.',
                    'Kartu di bagian atas menampilkan jumlah generus aktif, guru aktif, dan data lain. Untuk peran desa atau kelompok, angkanya hanya mencakup wilayah Anda.',
                    'Gunakan kotak pintasan di bawahnya untuk membuka menu yang sering dipakai.',
                ]],
            ],
        ],
        [
            'id' => 'generus',
            'title' => 'Generus',
            'permission' => ['view-generus', 'manage-generus'],
            'summary' => 'Data murid/generus, penempatan, foto, ID card, serta import dan export.',
            'features' => [
                ['title' => 'Melihat daftar dan detail generus', 'steps' => [
                    'Buka "Generus" lalu "Data Generus". Daftar tampil 25 data per halaman.',
                    'Klik nama generus untuk melihat detail: identitas, foto, kelas, dan riwayat penempatan.',
                ]],
                ['title' => 'Menambah generus baru', 'permission' => ['manage-generus'], 'steps' => [
                    'Buka "Generus" lalu "Tambah Generus".',
                    'Pada "Asal Generus" pilih "Luar Daerah / Data Baru" untuk generus yang belum pernah tercatat.',
                    'Isi nama lengkap (wajib). Nomor induk dibuat otomatis oleh sistem berformat tahun-bulan lalu 4 digit urut, misalnya 26090001.',
                    'Unggah foto bila ada (JPG, PNG, atau WEBP, maksimal 2 MB, rasio 3:4). Foto dipakai di detail dan ID card.',
                    'Isi data referensi seperti madrasah, nama ayah, dan nama ibu. Saat mengetik nama orang tua, sistem menyarankan nama yang sudah pernah ada. Klik saran agar penulisannya sama; bila tidak ada yang cocok, ketik manual.',
                    'Pilih "Kelas Sekolah" dari daftar. "Kelas KBM" otomatis ikut sama dan boleh diganti.',
                    'Pilih penempatan: Desa, Kelompok, dan Jenjang. Daerah otomatis Karawang Timur. Kelompok menyesuaikan desa yang dipilih.',
                    'Klik "Simpan Generus". Nama ayah dan ibu otomatis masuk ke daftar Orang Tua / Wali.',
                ]],
                ['title' => 'Menerima generus pindah sambung dari dalam daerah', 'permission' => ['manage-generus'], 'steps' => [
                    'Di "Tambah Generus", pilih "Dalam Daerah (Pindah Sambung)".',
                    'Pilih desa asal (opsional) dan kelompok asal, lalu pilih nama generus dari daftar yang muncul.',
                    'Data lamanya terisi otomatis dan bisa disesuaikan. Nomor induk tidak berubah.',
                    'Tentukan desa, kelompok, dan jenjang tujuan, lalu klik "Terima Pindah Sambung".',
                ]],
                ['title' => 'Mengubah data atau memindahkan penempatan', 'permission' => ['manage-generus'], 'steps' => [
                    'Buka detail generus lalu klik "Edit".',
                    'Ubah isian yang perlu. Bila desa, kelompok, atau jenjang diganti, penempatan lama ditutup dan penempatan baru dicatat di "Riwayat Penempatan".',
                    'Klik "Simpan Perubahan".',
                ]],
                ['title' => 'Mencatat generus yang pindah sambung ke luar', 'permission' => ['manage-generus'], 'steps' => [
                    'Di halaman Edit, ubah "Status Generus" menjadi "Pindah Sambung".',
                    'Pilih tujuan: "Daerah terdaftar di sistem" (nanti diterima kelompok tujuan) atau "Luar daerah".',
                    'Simpan. Untuk tujuan daerah terdaftar, generus akan muncul di daftar pindah sambung milik kelompok yang menerimanya.',
                ]],
                ['title' => 'Mencetak ID card', 'permission' => ['manage-generus'], 'steps' => [
                    'Buka detail generus, klik "ID Card", lalu cetak dari browser. Kartu memuat foto dan QR code nomor induk.',
                ]],
                ['title' => 'Menghapus generus', 'permission' => ['manage-generus'], 'steps' => [
                    'Buka detail generus, klik "Hapus", lalu konfirmasi. Data tidak hilang permanen dan dapat dipulihkan administrator database. Akun login generus itu ikut dinonaktifkan.',
                ]],
                ['title' => 'Export dan import Excel (XLSX)', 'permission' => ['manage-generus'], 'steps' => [
                    'Di "Data Generus", klik "Export XLSX" untuk mengunduh seluruh data.',
                    'Ubah atau tambahkan baris di Excel. Jangan mengubah judul kolom; kolom kode (kode desa, kelompok, jenjang, kelas) harus sesuai master data.',
                    'Pilih file pada "Import Database Generus" lalu klik "Import XLSX".',
                    'Jika ada baris yang salah, seluruh file dibatalkan dan pesan menyebutkan nomor barisnya. Perbaiki lalu impor ulang.',
                ]],
            ],
        ],
        [
            'id' => 'guru',
            'title' => 'Guru',
            'permission' => ['view-teachers', 'manage-teachers'],
            'summary' => 'Data guru, penempatan di desa dan kelompok, murid asuh, ID card, dan import/export.',
            'features' => [
                ['title' => 'Menambah guru', 'permission' => ['manage-teachers'], 'steps' => [
                    'Buka "Guru" lalu "Data Guru". Formulir "Tambah Guru" ada di sisi kiri.',
                    'Isi nama (wajib), jenis kelamin, telepon, email, dan foto bila ada.',
                    'Pilih Desa dan Kelompok. Untuk akun peran desa atau kelompok, isian ini otomatis terisi dan terkunci sesuai wilayah akun.',
                    'Klik "Simpan Guru". Nomor induk guru dibuat otomatis berformat tahun-bulan, 99, lalu 3 digit urut, misalnya 260999001, sehingga berbeda dari nomor generus.',
                ]],
                ['title' => 'Mengubah guru, ID card, dan menghapus', 'permission' => ['manage-teachers'], 'steps' => [
                    'Pada daftar guru klik "Edit" untuk mengubah data, atau "ID Card" untuk mencetak kartu.',
                    'Tombol "Hapus Guru" ada di halaman Edit. Guru yang masih tercatat di sesi KBM, evaluasi, tindak lanjut, atau penugasan tidak bisa dihapus; ubah statusnya menjadi Nonaktif.',
                ]],
                ['title' => 'Guru memilih murid asuhnya ("Murid Saya")', 'permission' => ['manage-my-students'], 'steps' => [
                    'Login sebagai guru, lalu buka "Guru" lalu "Murid Saya".',
                    'Daftar berisi generus aktif di kelompok Anda. Centang generus yang menjadi murid Anda; gunakan kotak pencarian untuk mempercepat.',
                    'Klik "Simpan Daftar Murid".',
                    'Setelah itu, di menu Absensi, Evaluasi, Tindak Lanjut, Rapor, dan lainnya, Anda hanya melihat dan mengisi data murid yang dicentang.',
                ]],
                ['title' => 'Admin mengatur murid seorang guru', 'permission' => ['manage-teachers'], 'steps' => [
                    'Buka "Edit" pada guru yang dimaksud, lalu klik tombol "Murid" di bagian atas.',
                    'Centang generus dari kelompok guru itu dan simpan. Guru harus sudah memiliki kelompok.',
                ]],
                ['title' => 'Export dan import Excel guru', 'permission' => ['manage-teachers'], 'steps' => [
                    'Di "Data Guru", klik "Export XLSX", ubah di Excel, lalu unggah kembali lewat "Import XLSX".',
                    'Baris dengan nomor induk yang sama diperbarui. Baris tanpa nomor induk menjadi guru baru dengan nomor otomatis.',
                    'Kolom desa dan kelompok diisi dengan nama yang sama seperti di master data.',
                ]],
            ],
        ],
        [
            'id' => 'wali',
            'title' => 'Orang Tua / Wali dan Komunikasi',
            'permission' => ['view-guardians', 'manage-guardians', 'view-parent-communications', 'manage-parent-communications'],
            'summary' => 'Daftar orang tua yang terhubung ke generus, dan catatan komunikasi guru dengan orang tua.',
            'features' => [
                ['title' => 'Daftar orang tua terisi otomatis', 'permission' => ['view-guardians', 'manage-guardians'], 'steps' => [
                    'Saat nama ayah atau ibu diisi pada data generus, sistem otomatis menambahkannya ke "Orang Tua / Wali" dan menghubungkannya dengan generus tersebut.',
                    'Nama yang sama (huruf besar/kecil diabaikan) dianggap satu orang, sehingga kakak dan adik terhubung ke orang tua yang sama. Kolom "Anak (Generus)" menampilkan anak-anaknya.',
                    'Bila ada dua orang tua berbeda yang namanya kebetulan sama, bedakan penulisannya secara manual.',
                ]],
                ['title' => 'Menambah atau mengubah data wali manual', 'permission' => ['manage-guardians'], 'steps' => [
                    'Buka "Orang Tua / Wali" lalu "Data Wali", isi formulir di kiri, dan klik simpan. Klik "Edit" pada baris untuk mengubah telepon, alamat, atau status.',
                    'Export dan import Excel tersedia di bagian atas halaman.',
                ]],
                ['title' => 'Mencatat komunikasi dengan orang tua', 'permission' => ['manage-parent-communications'], 'steps' => [
                    'Buka "Orang Tua / Wali" lalu "Komunikasi Orang Tua".',
                    'Pilih generus (guru hanya melihat murid asuhnya), lalu pilih orang tua/wali yang terhubung dengannya bila perlu.',
                    'Pilih saluran (WhatsApp, telepon, tatap muka, surat, lainnya), tanggal, perihal, dan isi komunikasi, lalu simpan.',
                    'Riwayat tampil di sisi kanan lengkap dengan nama pencatatnya.',
                ]],
            ],
        ],
        [
            'id' => 'catatan-murid',
            'title' => 'Catatan Perkembangan Murid',
            'permission' => ['manage-learning-attendance', 'view-learning-attendance', 'manage-evaluations', 'view-evaluations', 'manage-follow-ups', 'view-follow-ups', 'manage-progress-tracking', 'view-progress-tracking', 'manage-milestones', 'view-milestones', 'manage-munaqosah', 'view-munaqosah', 'manage-report-cards', 'view-report-cards', 'manage-annual-audit', 'view-annual-audit'],
            'summary' => 'Absensi, nilai, tindak lanjut, progress, milestone, munaqosah, rapor, dan audit tahunan per murid.',
            'features' => [
                ['title' => 'Cara umum mengisi', 'steps' => [
                    'Buka menu yang dituju. Formulir tambah ada di sisi kiri dan daftar di sisi kanan.',
                    'Pilih generus dari daftar. Guru hanya melihat murid yang sudah dicentang di "Murid Saya"; peran desa atau kelompok hanya melihat generus di wilayahnya.',
                    'Isi kolom yang diminta lalu klik "Simpan". Pada beberapa menu, klik "Edit" pada baris untuk mengoreksi.',
                    'Peran baca-saja hanya melihat daftar tanpa formulir.',
                ]],
                ['title' => 'Absensi Sesi (KBM)', 'permission' => ['manage-learning-attendance', 'view-learning-attendance'], 'steps' => [
                    'Buka "KBM" lalu "Absensi Sesi". Pilih sesi, pilih generus, tentukan status hadir, dan tambahkan catatan.',
                    'Absensi satu generus untuk satu sesi hanya bisa dicatat sekali; gunakan Edit bila perlu mengubah.',
                    'Daftar hadir per sesi: buka "KBM" lalu "Sesi KBM", klik "Daftar Hadir" pada sesi, pilih status tiap generus, lalu "Simpan Daftar Hadir". Generus yang belum dipilih tidak dicatat.',
                    'Absensi cepat: di halaman Daftar Hadir, pilih "Scan QR Code" (arahkan kamera ke QR pada ID card), "Scan RFID" (tempelkan kartu pada pembaca USB), atau "Scan Wajah" (hanya untuk generus yang fotonya sudah diunggah). Scan langsung tercatat hadir. UID RFID diisi di form generus.',
                    'Sesi tingkat desa diisi oleh Perwakilan PPG Desa (seluruh generus desa itu). Sesi tingkat kelompok diisi oleh Pelaksana PPG Kelompok, hanya untuk murid masing-masing.',
                ]],
                ['title' => 'Nilai Evaluasi', 'permission' => ['manage-evaluations', 'view-evaluations'], 'steps' => [
                    'Buka "Evaluasi" lalu "Nilai Evaluasi". Pilih evaluasi dan generus, isi skor 0 sampai 100 dan grade.',
                    'Satu generus hanya punya satu nilai per evaluasi.',
                ]],
                ['title' => 'Tindak Lanjut dan Progress Tracking', 'permission' => ['manage-follow-ups', 'view-follow-ups', 'manage-progress-tracking', 'view-progress-tracking'], 'steps' => [
                    'Tindak Lanjut: pilih generus, isi judul, prioritas, tanggal, status, catatan, dan tindakan selanjutnya. Untuk guru, nama guru pembina otomatis diisi akun Anda.',
                    'Progress Tracking (menu Kurikulum): pilih generus, tahun akademik, semester, periode, status, skor, dan target berikutnya.',
                ]],
                ['title' => 'Milestone, Munaqosah, dan Rapor', 'permission' => ['manage-milestones', 'view-milestones', 'manage-munaqosah', 'view-munaqosah', 'manage-report-cards', 'view-report-cards'], 'steps' => [
                    'Milestone: catat pencapaian beserta tanggal, status, dan skor.',
                    'Munaqosah: catat judul, jenis, skor, hasil, dan status ujian.',
                    'Rapor: pilih generus, tahun akademik, dan semester, lalu isi nilai akhir, predikat, rekomendasi, dan catatan. Satu generus hanya punya satu rapor per semester.',
                ]],
            ],
        ],
        [
            'id' => 'program',
            'title' => 'Kurikulum, Program Pembinaan, dan Kegiatan',
            'permission' => ['manage-curriculum', 'view-curriculum', 'manage-learning-materials', 'view-learning-materials', 'manage-activity-schedules', 'view-activity-schedules', 'manage-learning-sessions', 'view-learning-sessions', 'manage-training', 'view-training', 'manage-communication', 'view-communication', 'manage-organization-units', 'view-organization-units', 'manage-assignments', 'view-assignments'],
            'summary' => 'Menu pendukung: program kurikulum, materi, jadwal, sesi KBM, pelatihan guru, komunikasi, dan struktur organisasi.',
            'features' => [
                ['title' => 'Cara mengisi menu-menu ini', 'steps' => [
                    'Buka menunya dari sidebar. Isi formulir di kiri lalu klik simpan; data langsung muncul di daftar.',
                    'Urutan yang disarankan: Program Kurikulum, lalu Materi, lalu Sesi KBM, lalu Absensi dan Evaluasi. Pastikan Master Data (jenjang, tahun akademik, semester) sudah terisi lebih dulu.',
                    'Jadwal Program dicatat di "Program Pembinaan", lalu realisasinya dicatat di "Pelaksanaan Program".',
                    'Menu "Komunikasi" dipakai untuk pesan terkait pelatihan; komunikasi dengan orang tua ada di menu "Orang Tua / Wali".',
                    'Struktur Organisasi dan Penempatan dipakai untuk mencatat unit dan penugasan pengurus.',
                ]],
            ],
        ],
        [
            'id' => 'laporan',
            'title' => 'Laporan',
            'permission' => ['view-reports'],
            'summary' => 'Ringkasan data untuk dibaca.',
            'features' => [
                ['title' => 'Melihat laporan', 'steps' => [
                    'Klik "Laporan" di sidebar untuk melihat ringkasan terbaru dari pelatihan dan data lain. Halaman ini hanya untuk dibaca.',
                ]],
            ],
        ],
        [
            'id' => 'master-data',
            'title' => 'Master Data',
            'permission' => ['view-master-data'],
            'summary' => 'Desa, Kelompok, Jenjang, Kelas, Tahun Akademik, dan Semester yang dipakai di seluruh aplikasi.',
            'features' => [
                ['title' => 'Menambah data master', 'permission' => ['manage-master-data'], 'steps' => [
                    'Buka "Master Data" lalu pilih submenu: Desa, Kelompok, Jenjang, Kelas, Tahun Akademik, atau Semester.',
                    'Isi formulir di bagian atas halaman lalu klik tombol "Tambah ...".',
                    'Daerah tidak perlu diisi karena seluruh data otomatis Karawang Timur.',
                    'Isi Desa lebih dulu sebelum Kelompok, dan Tahun Akademik sebelum Semester.',
                ]],
                ['title' => 'Mengubah atau menonaktifkan data', 'permission' => ['manage-master-data'], 'steps' => [
                    'Klik tulisan "Edit" pada baris data. Formulir perubahan terbuka di bawahnya.',
                    'Ubah isian, hilangkan centang "Aktif" bila data tidak dipakai lagi, lalu klik "Simpan Perubahan".',
                    'Data nonaktif tidak muncul di pilihan formulir lain, tetapi riwayatnya tetap tersimpan.',
                ]],
                ['title' => 'Daftar Kelompok', 'steps' => [
                    'Halaman Kelompok tampil dikelompokkan per desa dan berurutan abjad, lengkap dengan jumlah kelompok tiap desa.',
                ]],
                ['title' => 'Export dan import Excel master data', 'permission' => ['manage-master-data'], 'steps' => [
                    'Setiap submenu punya "Export XLSX" dan "Import XLSX". Gunakan file hasil export sebagai template.',
                    'Kunci pencocokan: Desa dengan nama; Kelompok dengan desa dan nama; Jenjang, Kelas, dan Tahun Akademik dengan kode; Semester dengan kode tahun akademik dan nama. Data yang cocok diperbarui, yang baru ditambahkan.',
                    'Kolom status diisi "aktif" atau "nonaktif".',
                ]],
            ],
        ],
        [
            'id' => 'pengguna',
            'title' => 'Pengguna dan Peran',
            'permission' => ['manage-users'],
            'summary' => 'Mengelola akun, peran, dan cakupan wilayah.',
            'features' => [
                ['title' => 'Menambah pengguna dan menentukan cakupan', 'steps' => [
                    'Buka "Manajemen Pengguna" lalu "Data Pengguna". Isi nama, email, password (minimal 8 karakter), dan status.',
                    'Pada "Peran dan Cakupan" centang peran. Untuk tiap peran pilih cakupannya: "Seluruh sistem", "Satu desa" (lalu pilih desanya), atau "Satu kelompok" (lalu pilih kelompoknya).',
                    'Klik simpan. Saat mengubah pengguna, kosongkan password bila tidak ingin menggantinya.',
                ]],
                ['title' => 'Peran bawaan', 'steps' => [
                    'PPG: melihat semua data (baca-saja), termasuk Master Data. Pilih cakupan "Seluruh sistem".',
                    'Perwakilan PPG Desa: melihat data di satu desa (baca-saja). Pilih cakupan "Satu desa".',
                    'Pelaksana PPG Kelompok: mengelola penuh data satu kelompok. Pilih cakupan "Satu kelompok".',
                    'Guru dan Generus: dibuat otomatis untuk akun berbasis nomor induk. Guru boleh mengisi catatan murid asuhnya.',
                    'Super Admin: akses penuh.',
                ]],
                ['title' => 'Mengatur peran dan izin', 'permission' => ['manage-users'], 'steps' => [
                    'Buka "Data Peran". Buat peran baru dengan nama dan kode, lalu centang izin yang diberikan.',
                    'Izin berawalan "view" hanya melihat; izin berawalan "manage" boleh menambah dan mengubah.',
                ]],
            ],
        ],
        [
            'id' => 'bantuan',
            'title' => 'Pertanyaan Umum',
            'summary' => 'Jawaban singkat untuk kendala yang sering terjadi.',
            'features' => [
                ['title' => 'Tombol tambah atau edit tidak muncul', 'steps' => [
                    'Akun Anda kemungkinan berperan baca-saja untuk menu tersebut. Hubungi admin bila perlu akses mengubah data.',
                ]],
                ['title' => 'Data yang saya cari tidak muncul', 'steps' => [
                    'Peran desa atau kelompok hanya melihat data wilayahnya. Guru hanya melihat murid yang dicentang di "Murid Saya".',
                    'Pastikan juga generus masih aktif dan berada di kelompok yang benar.',
                ]],
                ['title' => 'Import Excel gagal', 'steps' => [
                    'Baca pesan "Baris N: ..." lalu perbaiki baris tersebut. Import bersifat semua-atau-tidak, jadi tidak ada data yang masuk sebagian.',
                    'Pastikan file berformat .xlsx, judul kolom tidak diubah, dan kode atau nama desa, kelompok, serta jenjang sudah ada di Master Data.',
                ]],
                ['title' => 'Lupa password', 'steps' => [
                    'Hubungi admin untuk mendapatkan bantuan pengaturan ulang akun.',
                ]],
            ],
        ],
    ];

    $visibleTopics = collect($topics)->filter(fn (array $topic): bool => ! isset($topic['permission']) || \App\Support\Access::can(...$topic['permission']))->values();
@endphp

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Bantuan</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Tutorial Penggunaan Aplikasi</h2>
        <p class="mt-2 text-sm text-slate-500">Panduan langkah demi langkah per menu dan fitur. Hanya menu yang tersedia untuk akun Anda yang ditampilkan.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-4 lg:self-start">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <input id="tutorial-search" type="search" placeholder="Cari panduan..." class="mb-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                <nav class="space-y-1 text-sm" aria-label="Daftar topik tutorial">
                    @foreach ($visibleTopics as $topic)
                        <a href="#{{ $topic['id'] }}" class="block rounded-md px-2.5 py-1.5 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">{{ $topic['title'] }}</a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <div class="min-w-0 space-y-6" id="tutorial-topics">
            @foreach ($visibleTopics as $topic)
                <section id="{{ $topic['id'] }}" class="tutorial-topic scroll-mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-950">{{ $topic['title'] }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $topic['summary'] }}</p>

                    <div class="mt-5 space-y-3">
                        @foreach ($topic['features'] as $feature)
                            @if (! isset($feature['permission']) || \App\Support\Access::can(...$feature['permission']))
                                <details class="tutorial-feature group rounded-xl border border-slate-200" @if ($loop->first) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-slate-800 [&::-webkit-details-marker]:hidden">
                                        <span>{{ $feature['title'] }}</span>
                                        <span class="text-[10px] text-slate-400 transition-transform duration-200 group-open:rotate-180">▾</span>
                                    </summary>
                                    <ol class="list-decimal space-y-2 border-t border-slate-100 px-4 py-4 pl-9 text-sm text-slate-600">
                                        @foreach ($feature['steps'] as $step)
                                            <li>{{ $step }}</li>
                                        @endforeach
                                    </ol>
                                </details>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach

            <p id="tutorial-empty" class="hidden rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Tidak ada panduan yang cocok dengan pencarian Anda.</p>
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('tutorial-search');
            const topics = Array.from(document.querySelectorAll('.tutorial-topic'));
            const empty = document.getElementById('tutorial-empty');

            input.addEventListener('input', () => {
                const term = input.value.trim().toLowerCase();
                let visible = 0;

                topics.forEach((topic) => {
                    let topicMatches = false;

                    topic.querySelectorAll('.tutorial-feature').forEach((feature) => {
                        const matches = term === '' || feature.textContent.toLowerCase().includes(term) || topic.querySelector('h3').textContent.toLowerCase().includes(term);
                        feature.hidden = !matches;
                        feature.open = term !== '' && matches;
                        topicMatches = topicMatches || matches;
                    });

                    topic.hidden = !topicMatches;
                    visible += topicMatches ? 1 : 0;
                });

                empty.classList.toggle('hidden', visible > 0);
            });
        })();
    </script>
@endsection
