<?php

namespace App\Console\Commands;

use App\Http\Controllers\KontrolController;
use App\Models\ClassGrade;
use App\Models\MaterialCategory;
use App\Models\Semester;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Kontrol Harian hanya menampilkan materi lewat kategori yang kode-nya persis sama dengan
 * KontrolController::CATEGORIES (bacaan, hafalan, menulis, praktik-ibadah, akhlaq, kemandirian).
 * Perintah ini hanya membuat baris Kategori Materi yang masih kosong untuk tiap kelas KBM aktif
 * x semester, supaya admin tinggal menambah Bab Materi & Materi tanpa perlu buat kategorinya
 * satu per satu lewat UI. Aman dijalankan berkali-kali — kategori yang sudah ada dilewati.
 */
#[Signature('kontrol:ensure-categories {--semester=* : ID semester yang dituju; kosongkan untuk semua semester}')]
#[Description('Buat kategori materi Kontrol Harian (bacaan, hafalan, dst) yang belum ada untuk tiap kelas KBM aktif')]
class EnsureKontrolMaterialCategories extends Command
{
    public function handle(): int
    {
        $semesterIds = $this->option('semester');

        $semesters = $semesterIds === []
            ? Semester::all()
            : Semester::whereIn('id', $semesterIds)->get();

        if ($semesters->isEmpty()) {
            $this->error('Tidak ada semester yang cocok.');

            return self::FAILURE;
        }

        $classGrades = ClassGrade::where('is_active', true)->orderBy('sort_order')->get();

        $created = 0;

        foreach ($semesters as $semester) {
            foreach ($classGrades as $classGrade) {
                $sortOrder = 1;

                foreach (KontrolController::CATEGORIES as $code => $label) {
                    $category = MaterialCategory::firstOrCreate(
                        [
                            'class_grade_id' => $classGrade->id,
                            'semester_id' => $semester->id,
                            'code' => $code,
                        ],
                        [
                            'name' => $label,
                            'sort_order' => $sortOrder,
                            'is_active' => true,
                        ],
                    );

                    if ($category->wasRecentlyCreated) {
                        $created++;
                    }

                    $sortOrder++;
                }
            }
        }

        $this->info("Selesai. {$created} kategori materi baru dibuat (yang sudah ada dilewati).");
        $this->info('Lanjutkan isi Bab Materi dan Materi di masing-masing kategori lewat menu Kurikulum -> Kategori Materi.');

        return self::SUCCESS;
    }
}
