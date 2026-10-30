@php
    // Label ramah pengguna untuk setiap permission. Kunci yang tidak terdaftar akan
    // jatuh kembali ke nama permission aslinya.
    $permissionLabels = [
        'view-dashboard' => 'Lihat Dasbor',
        'view-tasks' => 'Lihat Musyawaroh',
        'manage-tasks' => 'Kelola Musyawaroh',
        'approve-registrations' => 'Setujui Pendaftaran',
        'view-generus' => 'Lihat Data Generus',
        'manage-generus' => 'Kelola Data Generus',
        'view-teachers' => 'Lihat Data Guru',
        'manage-teachers' => 'Kelola Data Guru',
        'manage-my-students' => 'Kelola Murid Saya',
        'view-training' => 'Lihat Program Pelatihan',
        'manage-training' => 'Kelola Program Pelatihan',
        'view-guardians' => 'Lihat Data Wali',
        'manage-guardians' => 'Kelola Data Wali',
        'view-parent-communications' => 'Lihat Komunikasi Orang Tua',
        'manage-parent-communications' => 'Kelola Komunikasi Orang Tua',
        'view-organization-units' => 'Lihat Struktur Organisasi',
        'manage-organization-units' => 'Kelola Struktur Organisasi',
        'view-assignments' => 'Lihat Penempatan',
        'manage-assignments' => 'Kelola Penempatan',
        'view-master-data' => 'Lihat Data Master',
        'manage-master-data' => 'Kelola Data Master',
        'view-learning-materials' => 'Lihat Kurikulum / Materi',
        'manage-learning-materials' => 'Kelola Kurikulum / Materi',
        'view-progress-tracking' => 'Lihat Progress Tracking',
        'manage-progress-tracking' => 'Kelola Progress Tracking',
        'view-download-files' => 'Lihat Download File',
        'manage-download-files' => 'Kelola Download File',
        'view-activity-schedules' => 'Lihat Jadwal Program',
        'manage-activity-schedules' => 'Kelola Jadwal Program',
        'view-activity-executions' => 'Lihat Pelaksanaan Program',
        'manage-activity-executions' => 'Kelola Pelaksanaan Program',
        'view-milestones' => 'Lihat Milestone',
        'manage-milestones' => 'Kelola Milestone',
        'view-learning-sessions' => 'Lihat Sesi KBM',
        'manage-learning-sessions' => 'Kelola Sesi KBM',
        'view-learning-attendance' => 'Lihat Absensi Sesi',
        'manage-learning-attendance' => 'Kelola Absensi Sesi',
        'view-evaluations' => 'Lihat Evaluasi & Nilai',
        'manage-evaluations' => 'Kelola Evaluasi & Nilai',
        'view-munaqosah' => 'Lihat Daftar Munaqosah',
        'manage-munaqosah' => 'Kelola Daftar Munaqosah',
        'view-ujian' => 'Lihat Daftar Ujian & Kontrol',
        'manage-ujian' => 'Kelola Daftar Ujian & Kontrol',
        'view-report-cards' => 'Lihat Rapor Generus',
        'manage-report-cards' => 'Kelola Rapor Generus',
        'view-communication' => 'Lihat Pesan dan Riwayat',
        'manage-communication' => 'Kelola Pesan dan Riwayat',
        'view-announcements' => 'Lihat Berita',
        'manage-announcements' => 'Kelola Berita',
        'view-reports' => 'Lihat Laporan',
        'manage-users' => 'Kelola Pengguna & Peran',
        'view-curriculum' => 'Lihat Kurikulum',
        'manage-curriculum' => 'Kelola Kurikulum',
        'view-follow-ups' => 'Lihat Tindak Lanjut',
        'manage-follow-ups' => 'Kelola Tindak Lanjut',
        'view-annual-audit' => 'Lihat Audit Tahunan',
        'manage-annual-audit' => 'Kelola Audit Tahunan',
    ];

    // Struktur permission mengikuti hierarki menu di sidebar (Grup > Menu > Sub-menu),
    // supaya saat mencentang hak akses jelas permission itu membuka menu mana.
    $permissionMenuMap = [
        'Utama' => [
            'Dashboard' => ['view-dashboard'],
            'Musyawaroh' => ['view-tasks', 'manage-tasks'],
            'Persetujuan Pendaftaran' => ['manage-users', 'approve-registrations'],
        ],
        'Kesiswaan' => [
            'Generus » Data Generus' => ['view-generus', 'manage-generus'],
            'Guru & Pelatihan » Data Guru' => ['view-teachers', 'manage-teachers'],
            'Guru & Pelatihan » Murid Saya' => ['manage-my-students'],
            'Guru & Pelatihan » Program Pelatihan' => ['view-training', 'manage-training'],
            'Orang Tua / Wali » Data Wali' => ['view-guardians', 'manage-guardians'],
            'Orang Tua / Wali » Komunikasi Orang Tua' => ['view-parent-communications', 'manage-parent-communications'],
            'Organisasi » Struktur Organisasi' => ['view-organization-units', 'manage-organization-units'],
            'Organisasi » Penempatan' => ['view-assignments', 'manage-assignments'],
        ],
        'Kurikulum & Pembinaan' => [
            'Master Data' => ['view-master-data', 'manage-master-data'],
            'Kurikulum » Materi' => ['view-learning-materials', 'manage-learning-materials'],
            'Kurikulum » Progress Tracking' => ['view-progress-tracking', 'manage-progress-tracking'],
            'Kurikulum » Download File' => ['view-download-files', 'manage-download-files'],
            'Kegiatan Pembinaan » Jadwal Program' => ['view-activity-schedules', 'manage-activity-schedules'],
            'Kegiatan Pembinaan » Pelaksanaan Program' => ['view-activity-executions', 'manage-activity-executions'],
            'Kegiatan Pembinaan » Milestone' => ['view-milestones', 'manage-milestones'],
            'Kegiatan Pembinaan » Sesi KBM' => ['view-learning-sessions', 'manage-learning-sessions'],
            'Kegiatan Pembinaan » Absensi Sesi' => ['view-learning-attendance', 'manage-learning-attendance'],
            'Penilaian » Evaluasi & Nilai Evaluasi' => ['view-evaluations', 'manage-evaluations'],
            'Penilaian » Daftar Munaqosah' => ['view-munaqosah', 'manage-munaqosah'],
            'Penilaian » Daftar Ujian & Kontrol' => ['view-ujian', 'manage-ujian'],
            'Penilaian » Rapor Generus' => ['view-report-cards', 'manage-report-cards'],
        ],
        'Komunikasi & Laporan' => [
            'Komunikasi & Berita » Pesan dan Riwayat' => ['view-communication', 'manage-communication'],
            'Komunikasi & Berita » Berita' => ['view-announcements', 'manage-announcements'],
            'Laporan' => ['view-reports'],
        ],
        'Pengaturan' => [
            'Manajemen Pengguna' => ['manage-users'],
        ],
    ];

    $selectedPermissionIds = old('permissions', $role?->permissions->pluck('id')->all() ?? []);
    $permissionsBySlug = $permissions->keyBy('slug');
    $mappedSlugs = collect($permissionMenuMap)->flatten(2)->unique()->all();
    $otherPermissions = $permissions->reject(fn ($permission) => in_array($permission->slug, $mappedSlugs));
@endphp

<div class="space-y-4">
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Nama Peran <span class="text-rose-500">*</span></label>
        <input id="name" name="name" type="text" required value="{{ old('name', $role?->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="slug">Kode Peran <span class="text-rose-500">*</span></label>
        <input id="slug" name="slug" type="text" required value="{{ old('slug', $role?->slug) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="description">Deskripsi</label>
        <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('description', $role?->description) }}</textarea>
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="is_active">Status</label>
        <select id="is_active" name="is_active" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
            <option value="1" @selected(old('is_active', $role?->is_active ?? true) == 1)>Aktif</option>
            <option value="0" @selected(old('is_active', $role?->is_active ?? true) == 0)>Nonaktif</option>
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700">Hak Akses</label>
        <p class="mb-3 text-xs text-slate-500">Hak akses dikelompokkan sesuai menu &amp; sub-menu di sidebar, sehingga lebih mudah menentukan mana yang perlu dicentang.</p>

        <div class="space-y-3">
            @foreach ($permissionMenuMap as $groupLabel => $menus)
                @php
                    $groupPermissionIds = collect($menus)->flatten()->map(fn ($slug) => $permissionsBySlug->get($slug)?->id)->filter()->values();
                @endphp
                @continue($groupPermissionIds->isEmpty())

                <details open class="group rounded-lg border border-slate-200 bg-slate-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-slate-800 [&::-webkit-details-marker]:hidden">
                        <span>{{ $groupLabel }}</span>
                        <span class="text-[10px] text-slate-400 transition-transform duration-200 group-open:rotate-180">▾</span>
                    </summary>

                    <div class="space-y-3 border-t border-slate-200 p-3">
                        @foreach ($menus as $menuLabel => $slugs)
                            @php
                                $menuPermissions = collect($slugs)->map(fn ($slug) => $permissionsBySlug->get($slug))->filter();
                            @endphp
                            @continue($menuPermissions->isEmpty())

                            <div class="rounded-md border border-slate-200 bg-white p-2.5">
                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $menuLabel }}</p>
                                <div class="space-y-1.5">
                                    @foreach ($menuPermissions as $permission)
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selectedPermissionIds)) class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                            <span>{{ $permissionLabels[$permission->slug] ?? $permission->name }} <span class="text-slate-400">({{ $permission->slug }})</span></span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endforeach

            @if ($otherPermissions->isNotEmpty())
                <details class="group rounded-lg border border-slate-200 bg-slate-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold text-slate-800 [&::-webkit-details-marker]:hidden">
                        <span>Lainnya</span>
                        <span class="text-[10px] text-slate-400 transition-transform duration-200 group-open:rotate-180">▾</span>
                    </summary>

                    <div class="space-y-1.5 border-t border-slate-200 p-3">
                        @foreach ($otherPermissions as $permission)
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selectedPermissionIds)) class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                <span>{{ $permissionLabels[$permission->slug] ?? $permission->name }} <span class="text-slate-400">({{ $permission->slug }})</span></span>
                            </label>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>
    </div>
</div>
