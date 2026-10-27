<?php

namespace Database\Seeders;

use App\Models\Dapukan;
use Illuminate\Database\Seeder;

/**
 * The fixed dapukan (position) catalog for each of the three organisation roles. Safe to run
 * repeatedly: matched on (role_slug, group_label, name).
 */
class DapukanSeeder extends Seeder
{
    /**
     * @var list<array{role_slug: string, group_label: ?string, name: string, can_approve?: bool}>
     */
    private const DAPUKANS = [
        // Daerah (role_slug: ppg)
        ['role_slug' => 'ppg', 'group_label' => null, 'name' => 'Pembina PPG'],
        ['role_slug' => 'ppg', 'group_label' => 'PH PPG', 'name' => 'Ketua PPG', 'can_approve' => true],
        ['role_slug' => 'ppg', 'group_label' => 'PH PPG', 'name' => 'Wakil Ketua', 'can_approve' => true],
        ['role_slug' => 'ppg', 'group_label' => 'PH PPG', 'name' => 'Sekretaris', 'can_approve' => true],
        ['role_slug' => 'ppg', 'group_label' => 'PH PPG', 'name' => 'Bendahara', 'can_approve' => true],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang Kurikulum'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang Tenaga Pendidik'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang Tahfidz'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang Kemandirian'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang Olahraga & Seni'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang Penggalang Dana'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang Sarana Prasarana'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang KMM (Muda-Mudi)'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang Keputrian'],
        ['role_slug' => 'ppg', 'group_label' => 'Seksi Bidang', 'name' => 'Bidang BK (Bimbingan Konseling)'],

        // Desa (role_slug: perwakilan-ppg-desa)
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => null, 'name' => 'Pembina Desa'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang Pendidikan', 'name' => 'Bidang Kurikulum'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang Pendidikan', 'name' => 'Bidang Tenaga Pendidik'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang Pendidikan', 'name' => 'Bidang Tahfidz'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang Pendidikan', 'name' => 'Bidang Kemandirian'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang Pendidikan', 'name' => 'Bidang Olahraga & Seni'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang Pembiayaan & Pengadaan Sarana Prasarana', 'name' => 'Bidang Penggalang Dana'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang Pembiayaan & Pengadaan Sarana Prasarana', 'name' => 'Bidang Sarana & Prasarana'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang KMM', 'name' => 'Ketua Muda-Mudi'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang KMM', 'name' => 'Wakil Ketua'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang KMM', 'name' => 'Sekretaris'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang KMM', 'name' => 'Penerobos'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang KMM', 'name' => 'Bendahara'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => 'Bidang KMM', 'name' => 'Keputrian'],
        ['role_slug' => 'perwakilan-ppg-desa', 'group_label' => null, 'name' => 'Bidang BK'],

        // Kelompok (role_slug: pelaksana-ppg-kelompok)
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => null, 'name' => 'Unsur Pembina Generus'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Guru Generus', 'name' => 'MT'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Guru Generus', 'name' => 'MS'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Guru Generus', 'name' => 'Guru Bantu'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => null, 'name' => 'Bidang Pembiayaan dan Sarana Prasarana'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Bidang KMM', 'name' => 'Ketua Muda-Mudi'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Bidang KMM', 'name' => 'Wakil Ketua'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Bidang KMM', 'name' => 'Sekretaris'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Bidang KMM', 'name' => 'Penerobos'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Bidang KMM', 'name' => 'Bendahara'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => 'Bidang KMM', 'name' => 'Keputrian'],
        ['role_slug' => 'pelaksana-ppg-kelompok', 'group_label' => null, 'name' => 'Bidang BK'],
    ];

    public function run(): void
    {
        foreach (self::DAPUKANS as $sortOrder => $dapukan) {
            Dapukan::updateOrCreate(
                [
                    'role_slug' => $dapukan['role_slug'],
                    'group_label' => $dapukan['group_label'],
                    'name' => $dapukan['name'],
                ],
                [
                    'sort_order' => $sortOrder,
                    'can_approve' => $dapukan['can_approve'] ?? false,
                    'is_active' => true,
                ],
            );
        }
    }
}
