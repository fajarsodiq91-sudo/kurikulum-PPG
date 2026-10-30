<div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-slate-200 px-4">
    <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-9 w-9 shrink-0 object-contain">
    <div>
        <p class="text-xs font-bold leading-tight tracking-wide text-slate-900">PPG Bidang Pendidikan</p>
        <p class="text-[11px] leading-tight text-slate-500">Karawang Timur</p>
    </div>
</div>

<div class="px-3 pt-3">
    <input type="search" data-sidebar-search placeholder="Cari menu..." aria-label="Cari menu"
        class="w-full rounded-md border border-slate-200 px-3 py-1.5 text-xs text-slate-700 placeholder:text-slate-400 focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400">
</div>

<nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-3" aria-label="Navigasi utama">
    <p data-sidebar-empty class="hidden px-2 py-1.5 text-[12px] text-slate-400">Menu tidak ditemukan.</p>

    @php
        $navigationGroups = [
            ['label' => 'Utama', 'sections' => [
                ['label' => 'Dashboard', 'permission' => 'view-dashboard', 'route' => 'dashboard', 'icon' => '◈'],
                ['label' => 'Musyawaroh', 'permission' => ['view-tasks', 'manage-tasks'], 'route' => 'tasks.index', 'icon' => '☑'],
                ['label' => 'Persetujuan Pendaftaran', 'permission' => ['manage-users', 'approve-registrations'], 'route' => 'registrations.pending', 'icon' => '✓'],
            ]],
            ['label' => 'Kesiswaan', 'sections' => [
                ['label' => 'Generus', 'permission' => ['view-generus', 'manage-generus'], 'icon' => '◎', 'items' => [
                    ['label' => 'Data Generus', 'route' => 'generus.index'],
                    ['label' => 'Tambah Generus', 'route' => 'generus.create', 'permission' => 'manage-generus'],
                ]],
                ['label' => 'Guru & Pelatihan', 'icon' => '✎', 'items' => [
                    ['label' => 'Data Guru', 'route' => 'teachers.index', 'permission' => ['view-teachers', 'manage-teachers']],
                    ['label' => 'Murid Saya', 'route' => 'my-students.edit', 'permission' => 'manage-my-students'],
                    ['label' => 'Program Pelatihan', 'route' => 'trainings.index', 'permission' => ['view-training', 'manage-training']],
                ]],
                ['label' => 'Orang Tua / Wali', 'permission' => ['view-guardians', 'manage-guardians'], 'icon' => '⌂', 'items' => [
                    ['label' => 'Data Wali', 'route' => 'guardians.index'],
                    ['label' => 'Komunikasi Orang Tua', 'route' => 'parent-communications.index', 'permission' => ['view-parent-communications', 'manage-parent-communications']],
                ]],
                ['label' => 'Organisasi', 'permission' => ['view-organization-units', 'manage-organization-units'], 'icon' => '▣', 'items' => [
                    ['label' => 'Struktur Organisasi', 'route' => 'organization-units.index'],
                    ['label' => 'Penempatan', 'route' => 'assignments.index', 'permission' => ['view-assignments', 'manage-assignments']],
                ]],
            ]],
            ['label' => 'Kurikulum & Pembinaan', 'sections' => [
                ['label' => 'Master Data', 'permission' => 'view-master-data', 'icon' => '⌘', 'items' => [
                    ['label' => 'Desa', 'route' => 'master-data.villages.index'],
                    ['label' => 'Kelompok', 'route' => 'master-data.groups.index'],
                    ['label' => 'Jenjang', 'route' => 'master-data.levels.index'],
                    ['label' => 'Kelas', 'route' => 'master-data.class-grades.index'],
                    ['label' => 'Tahun Akademik', 'route' => 'master-data.academic-years.index'],
                    ['label' => 'Semester', 'route' => 'master-data.semesters.index'],
                    ['label' => 'Konversi Nilai', 'route' => 'master-data.grade-scales.index'],
                    ['label' => 'Dapukan', 'route' => 'master-data.dapukans.index'],
                ]],
                ['label' => 'Kurikulum', 'permission' => ['view-learning-materials', 'manage-learning-materials'], 'icon' => '▥', 'items' => [
                    ['label' => 'Kategori Materi', 'route' => 'material-categories.index'],
                    ['label' => 'Bab Materi', 'route' => 'material-chapters.index'],
                    ['label' => 'Materi', 'route' => 'learning-materials.index'],
                    ['label' => 'Progress Tracking', 'route' => 'progress-tracks.index', 'permission' => ['view-progress-tracking', 'manage-progress-tracking']],
                ]],
                ['label' => 'Kegiatan Pembinaan', 'icon' => '◷', 'items' => [
                    ['label' => 'Jadwal Program', 'route' => 'activity-schedules.index', 'permission' => ['view-activity-schedules', 'manage-activity-schedules']],
                    ['label' => 'Pelaksanaan Program', 'route' => 'activity-executions.index', 'permission' => ['view-activity-executions', 'manage-activity-executions']],
                    ['label' => 'Milestone', 'route' => 'milestones.index', 'permission' => ['view-milestones', 'manage-milestones']],
                    ['label' => 'Sesi KBM', 'route' => 'learning-sessions.index', 'permission' => ['view-learning-sessions', 'manage-learning-sessions']],
                    ['label' => 'Absensi Sesi', 'route' => 'session-attendances.index', 'permission' => ['view-learning-attendance', 'manage-learning-attendance']],
                ]],
                ['label' => 'Penilaian', 'icon' => '✓', 'items' => [
                    ['label' => 'Evaluasi', 'route' => 'evaluations.index', 'permission' => ['view-evaluations', 'manage-evaluations']],
                    ['label' => 'Nilai Evaluasi', 'route' => 'evaluation-scores.index', 'permission' => ['view-evaluations', 'manage-evaluations']],
                    ['label' => 'Daftar Munaqosah', 'route' => 'munaqosahs.index', 'permission' => ['view-munaqosah', 'manage-munaqosah']],
                    ['label' => 'Daftar Ujian', 'route' => 'ujians.index', 'permission' => ['view-ujian', 'manage-ujian']],
                    ['label' => 'Kontrol Bacaan', 'route' => 'kontrol.bacaan', 'permission' => ['view-ujian', 'manage-ujian']],
                    ['label' => 'Kontrol Hafalan', 'route' => 'kontrol.hafalan', 'permission' => ['view-ujian', 'manage-ujian']],
                    ['label' => 'Kontrol Menulis', 'route' => 'kontrol.menulis', 'permission' => ['view-ujian', 'manage-ujian']],
                    ['label' => 'Kontrol Praktik Ibadah', 'route' => 'kontrol.praktik-ibadah', 'permission' => ['view-ujian', 'manage-ujian']],
                    ['label' => 'Kontrol Akhlaq', 'route' => 'kontrol.akhlaq', 'permission' => ['view-ujian', 'manage-ujian']],
                    ['label' => 'Kontrol Kemandirian', 'route' => 'kontrol.kemandirian', 'permission' => ['view-ujian', 'manage-ujian']],
                    ['label' => 'Rapor Generus', 'route' => 'report-cards.index', 'permission' => ['view-report-cards', 'manage-report-cards']],
                ]],
            ]],
            ['label' => 'Komunikasi & Laporan', 'sections' => [
                ['label' => 'Komunikasi & Berita', 'icon' => '✉', 'items' => [
                    ['label' => 'Pesan dan Riwayat', 'route' => 'communications.index', 'permission' => ['view-communication', 'manage-communication']],
                    ['label' => 'Berita', 'route' => 'announcements.index', 'permission' => ['view-announcements', 'manage-announcements']],
                ]],
                ['label' => 'Laporan', 'permission' => 'view-reports', 'route' => 'reports.index', 'icon' => '▤'],
            ]],
            ['label' => 'Pengaturan', 'sections' => [
                ['label' => 'Manajemen Pengguna', 'permission' => 'manage-users', 'icon' => '⚙', 'items' => [
                    ['label' => 'Data Pengguna', 'route' => 'users.index'],
                    ['label' => 'Data Peran', 'route' => 'roles.index'],
                    ['label' => 'Data Wajah', 'route' => 'face-enrollment.index'],
                ]],
                ['label' => 'Tutorial', 'route' => 'tutorial', 'icon' => '?'],
            ]],
        ];
    @endphp

    @foreach ($navigationGroups as $group)
        @php
            $groupSections = collect($group['sections'])->filter(function ($section) {
                $sectionPermission = $section['permission'] ?? null;

                return (! $sectionPermission || \App\Support\Access::can(...\Illuminate\Support\Arr::wrap($sectionPermission)))
                    && (isset($section['route']) || collect($section['items'] ?? [])->contains(fn ($item) => ! isset($item['permission']) || \App\Support\Access::can(...\Illuminate\Support\Arr::wrap($item['permission']))));
            });
        @endphp

        @if ($groupSections->isNotEmpty())
            <div data-sidebar-group class="pt-3 first:pt-0">
                <p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ $group['label'] }}</p>

                @foreach ($groupSections as $section)
                    @php
                        $visibleItems = collect($section['items'] ?? [])->filter(fn ($item) => ! isset($item['permission']) || \App\Support\Access::can(...\Illuminate\Support\Arr::wrap($item['permission'])));
                        $isActive = isset($section['route'])
                            ? request()->routeIs($section['route'])
                            : $visibleItems->contains(fn ($item) => request()->routeIs($item['route']));
                    @endphp

                    @if (isset($section['route']))
                        <a data-sidebar-section href="{{ route($section['route']) }}" class="flex items-center gap-2.5 rounded-md border-l-2 px-2 py-1.5 text-[13px] font-semibold transition {{ $isActive ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-transparent text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                            <span class="w-4 text-center text-sm">{{ $section['icon'] ?? '•' }}</span>
                            <span>{{ $section['label'] }}</span>
                        </a>
                    @else
                        <details data-sidebar-section data-sidebar-accordion data-initial-open="{{ $isActive ? '1' : '0' }}" class="group" @if ($isActive) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between rounded-md border-l-2 px-2 py-1.5 text-[13px] font-semibold transition [&::-webkit-details-marker]:hidden {{ $isActive ? 'border-amber-500 text-slate-900 hover:bg-slate-100' : 'border-transparent text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                <span class="flex items-center gap-2.5">
                                    <span class="w-4 text-center text-sm">{{ $section['icon'] ?? '•' }}</span>
                                    <span>{{ $section['label'] }}</span>
                                </span>
                                <span class="text-[9px] text-slate-400 transition-transform duration-200 group-open:rotate-180">▾</span>
                            </summary>
                            <div class="ml-[13px] space-y-0.5 border-l border-slate-200 py-0.5 pl-4">
                                @foreach ($visibleItems as $item)
                                    <a href="{{ route($item['route']) }}" class="block rounded-md px-2 py-1 text-[12px] transition {{ request()->routeIs($item['route']) ? 'bg-amber-50 font-medium text-amber-700 hover:bg-amber-100' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @endforeach
            </div>
        @endif
    @endforeach
</nav>