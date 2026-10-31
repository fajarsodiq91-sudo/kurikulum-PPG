<div data-sidebar-header class="flex h-14 shrink-0 items-center gap-2.5 border-b border-slate-200 px-4">
    <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-9 w-9 shrink-0 object-contain">
    <div data-sidebar-label>
        <p class="text-xs font-bold leading-tight tracking-wide text-slate-900">PPG Bidang Pendidikan</p>
        <p class="text-[11px] leading-tight text-slate-500">Karawang Timur</p>
    </div>
</div>

<div class="hidden items-center justify-end border-b border-slate-200 px-3 py-1.5 lg:flex">
    <button type="button" data-sidebar-collapse-toggle aria-pressed="false" aria-label="Ringkas sidebar"
        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
        <svg data-sidebar-collapse-icon class="h-4 w-4 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11 17-5-5 5-5m7 10-5-5 5-5" />
        </svg>
    </button>
</div>

<div data-sidebar-label class="px-3 pt-3">
    <input type="search" data-sidebar-search placeholder="Cari menu..." aria-label="Cari menu"
        class="w-full rounded-md border border-slate-200 px-3 py-1.5 text-xs text-slate-700 placeholder:text-slate-400 focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400">
</div>

<nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-3" aria-label="Navigasi utama">
    <p data-sidebar-empty class="hidden px-2 py-1.5 text-[12px] text-slate-400">Menu tidak ditemukan.</p>

    @php
        $navigationGroups = [
            ['label' => 'Utama', 'sections' => [
                ['label' => 'Dashboard', 'permission' => 'view-dashboard', 'route' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Musyawaroh', 'permission' => ['view-tasks', 'manage-tasks'], 'route' => 'tasks.index', 'icon' => 'clipboard-check'],
                ['label' => 'Persetujuan Pendaftaran', 'permission' => ['manage-users', 'approve-registrations'], 'route' => 'registrations.pending', 'icon' => 'user-check'],
            ]],
            ['label' => 'Kesiswaan', 'sections' => [
                ['label' => 'Generus', 'permission' => ['view-generus', 'manage-generus'], 'icon' => 'academic-cap', 'items' => [
                    ['label' => 'Data Generus', 'route' => 'generus.index'],
                    ['label' => 'Tambah Generus', 'route' => 'generus.create', 'permission' => 'manage-generus'],
                ]],
                ['label' => 'Guru & Pelatihan', 'icon' => 'pencil', 'items' => [
                    ['label' => 'Data Guru', 'route' => 'teachers.index', 'permission' => ['view-teachers', 'manage-teachers']],
                    ['label' => 'Murid Saya', 'route' => 'my-students.edit', 'permission' => 'manage-my-students'],
                    ['label' => 'Program Pelatihan', 'route' => 'trainings.index', 'permission' => ['view-training', 'manage-training']],
                ]],
                ['label' => 'Orang Tua / Wali', 'permission' => ['view-guardians', 'manage-guardians'], 'icon' => 'users', 'items' => [
                    ['label' => 'Data Wali', 'route' => 'guardians.index'],
                    ['label' => 'Komunikasi Orang Tua', 'route' => 'parent-communications.index', 'permission' => ['view-parent-communications', 'manage-parent-communications']],
                ]],
                ['label' => 'Organisasi', 'permission' => ['view-organization-units', 'manage-organization-units'], 'icon' => 'grid', 'items' => [
                    ['label' => 'Struktur Organisasi', 'route' => 'organization-units.index'],
                    ['label' => 'Penempatan', 'route' => 'assignments.index', 'permission' => ['view-assignments', 'manage-assignments']],
                ]],
            ]],
            ['label' => 'Kurikulum & Pembinaan', 'sections' => [
                ['label' => 'Master Data', 'permission' => 'view-master-data', 'icon' => 'database', 'items' => [
                    ['label' => 'Desa', 'route' => 'master-data.villages.index'],
                    ['label' => 'Kelompok', 'route' => 'master-data.groups.index'],
                    ['label' => 'Jenjang', 'route' => 'master-data.levels.index'],
                    ['label' => 'Kelas', 'route' => 'master-data.class-grades.index'],
                    ['label' => 'Tahun Akademik', 'route' => 'master-data.academic-years.index'],
                    ['label' => 'Semester', 'route' => 'master-data.semesters.index'],
                    ['label' => 'Konversi Nilai', 'route' => 'master-data.grade-scales.index'],
                    ['label' => 'Dapukan', 'route' => 'master-data.dapukans.index'],
                ]],
                ['label' => 'Kurikulum', 'icon' => 'book-open', 'items' => [
                    ['label' => 'Kategori Materi', 'route' => 'material-categories.index', 'permission' => ['view-learning-materials', 'manage-learning-materials']],
                    ['label' => 'Bab Materi', 'route' => 'material-chapters.index', 'permission' => ['view-learning-materials', 'manage-learning-materials']],
                    ['label' => 'Materi', 'route' => 'learning-materials.index', 'permission' => ['view-learning-materials', 'manage-learning-materials']],
                    ['label' => 'Progress Tracking', 'route' => 'progress-tracks.index', 'permission' => ['view-progress-tracking', 'manage-progress-tracking']],
                    ['label' => 'Download File', 'route' => 'download-files.index', 'permission' => ['view-download-files', 'manage-download-files']],
                ]],
                ['label' => 'Kegiatan Pembinaan', 'icon' => 'clock', 'items' => [
                    ['label' => 'Jadwal Program', 'route' => 'activity-schedules.index', 'permission' => ['view-activity-schedules', 'manage-activity-schedules']],
                    ['label' => 'Pelaksanaan Program', 'route' => 'activity-executions.index', 'permission' => ['view-activity-executions', 'manage-activity-executions']],
                    ['label' => 'Milestone', 'route' => 'milestones.index', 'permission' => ['view-milestones', 'manage-milestones']],
                    ['label' => 'Sesi KBM', 'route' => 'learning-sessions.index', 'permission' => ['view-learning-sessions', 'manage-learning-sessions']],
                    ['label' => 'Absensi Sesi', 'route' => 'session-attendances.index', 'permission' => ['view-learning-attendance', 'manage-learning-attendance']],
                ]],
                ['label' => 'Penilaian', 'icon' => 'check-circle', 'items' => [
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
                ['label' => 'Komunikasi & Berita', 'icon' => 'envelope', 'items' => [
                    ['label' => 'Pesan dan Riwayat', 'route' => 'communications.index', 'permission' => ['view-communication', 'manage-communication']],
                    ['label' => 'Berita', 'route' => 'announcements.index', 'permission' => ['view-announcements', 'manage-announcements']],
                ]],
                ['label' => 'Laporan', 'permission' => 'view-reports', 'route' => 'reports.index', 'icon' => 'chart-bar'],
            ]],
            ['label' => 'Pengaturan', 'sections' => [
                ['label' => 'Manajemen Pengguna', 'permission' => 'manage-users', 'icon' => 'cog', 'items' => [
                    ['label' => 'Data Pengguna', 'route' => 'users.index'],
                    ['label' => 'Data Peran', 'route' => 'roles.index'],
                    ['label' => 'Data Wajah', 'route' => 'face-enrollment.index'],
                ]],
                ['label' => 'Tutorial', 'route' => 'tutorial', 'icon' => 'question-circle'],
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
                        <a data-sidebar-section href="{{ route($section['route']) }}" title="{{ $section['label'] }}" class="flex items-center gap-2.5 rounded-md border-l-2 px-2 py-1.5 text-[13px] font-semibold transition {{ $isActive ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-transparent text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                            <x-sidebar-icon :name="$section['icon'] ?? 'dot'" class="h-4 w-4 shrink-0" />
                            <span data-sidebar-label>{{ $section['label'] }}</span>
                        </a>
                    @else
                        <details data-sidebar-section data-sidebar-accordion data-initial-open="{{ $isActive ? '1' : '0' }}" class="group" @if ($isActive) open @endif>
                            <summary title="{{ $section['label'] }}" class="flex cursor-pointer list-none items-center justify-between rounded-md border-l-2 px-2 py-1.5 text-[13px] font-semibold transition [&::-webkit-details-marker]:hidden {{ $isActive ? 'border-amber-500 text-slate-900 hover:bg-slate-100' : 'border-transparent text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                                <span class="flex items-center gap-2.5">
                                    <x-sidebar-icon :name="$section['icon'] ?? 'dot'" class="h-4 w-4 shrink-0" />
                                    <span data-sidebar-label>{{ $section['label'] }}</span>
                                </span>
                                <span data-sidebar-label class="text-[9px] text-slate-400 transition-transform duration-200 group-open:rotate-180">▾</span>
                            </summary>
                            <div data-sidebar-label class="ml-[13px] space-y-0.5 border-l border-slate-200 py-0.5 pl-4">
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

<div data-sidebar-label class="shrink-0 border-t border-slate-200 px-3 py-2">
    <a href="https://alfajarlogic.com/" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-[10px] text-slate-400 transition hover:text-slate-600">
        <img src="{{ asset('images/alfajarlogic-logo.png') }}" alt="Alfajarlogic" class="h-4 w-auto">
        <span>developed by alfajarlogic.com</span>
    </a>
</div>