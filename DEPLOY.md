# Deploy ke Hostinger (generus.alfajarlogic.com)

Domain ini terhubung ke GitHub repo ini via fitur **Git auto-deploy** di hPanel
Hostinger. Setiap push ke branch `main`, Hostinger otomatis clone repo ke
`public_html` lalu menjalankan `deploy.sh`.

## Alur deploy normal

Perubahan kode PHP/Blade/migration — cukup push, tidak ada langkah tambahan:

```bash
git add .
git commit -m "pesan commit"
git push origin main
```

## Kalau ada perubahan CSS/JS

Server **tidak punya Node.js/npm**, jadi hasil build Vite (`public/build/`)
di-commit langsung ke repo (tidak di-gitignore). Build dulu secara lokal
sebelum push:

```bash
npm run build
git add public/build resources/
git commit -m "pesan commit"
git push origin main
```

## Kalau menambah package Composer baru

```bash
composer require nama/package
git add composer.json composer.lock
git commit -m "pesan commit"
git push origin main
```

Kalau package itu butuh ekstensi PHP yang belum tersedia di build container
Hostinger, lihat bagian "Keterbatasan server" di bawah.

## Kalau menambah migration baru

Tidak perlu langkah tambahan — `deploy.sh` selalu menjalankan
`php artisan migrate --force` di setiap deploy.

## Keterbatasan server (penting!)

1. **PHP di build container = 8.3.35**, berbeda dari PHP akun hosting
   (8.4.26, dipakai saat serving request nyata). `composer.json` punya
   `config.platform` override (`php`, `ext-gd`, `ext-zip`) agar resolusi
   dependency tetap kompatibel dengan versi 8.3.35 itu. Kalau ingin
   menambah package yang butuh PHP 8.4+, override ini harus disesuaikan
   atau dependency itu di-downgrade (`composer update --with-all-dependencies`).

2. **`proc_open` dan `symlink` dinonaktifkan** di server (baik di akun
   hosting maupun build container). Akibatnya:
   - Composer **tidak bisa** menjalankan script `"@php ..."` apa pun (semua
     script semacam itu di `composer.json` sudah dihapus/dipindah manual ke
     `deploy.sh`, yang dieksekusi langsung oleh shell, bukan oleh Composer).
   - `php artisan storage:link` tidak bisa dipakai. Sebagai gantinya, disk
     `public` di `config/filesystems.php` diarahkan langsung ke
     `public_path('storage')` (tanpa symlink). Folder itu dibuat otomatis
     di `deploy.sh` (`mkdir -p public/storage`).

3. **Tidak ada Node.js/npm** di server — lihat bagian CSS/JS di atas.

4. **Document root Hostinger terkunci ke `public_html`** (root project),
   tidak bisa diarahkan ke folder `public/`. Diatasi dengan `.htaccess` di
   root project yang me-rewrite semua request ke `public/`.

5. Proses "Cloning" Hostinger adalah fetch+checkout ke direktori yang sama
   (bukan wipe total) — file yang tidak di-git-track seperti `.env`,
   `storage/logs/`, dan `public/storage/` **persisten** antar-deploy.

## Troubleshooting cepat

Cek log aplikasi via SSH:
```bash
cd ~/domains/generus.alfajarlogic.com/public_html
tail -100 storage/logs/laravel.log
```

Jalankan migration/cache manual kalau auto-deploy gagal di tengah:
```bash
php artisan migrate --force
php artisan config:cache
```

## Kredensial default (ganti setelah login pertama!)

Lihat `database/seeders/DatabaseSeeder.php` untuk akun super admin default.
Ganti password-nya segera setelah login pertama kali di production.
Seed manual kalau belum ada datanya:
```bash
php artisan db:seed --force
```
