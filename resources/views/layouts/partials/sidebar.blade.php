<div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-slate-200 px-4">
    <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-9 w-9 shrink-0 object-contain">
    <div>
        <p class="text-xs font-bold leading-tight tracking-wide text-slate-900">PPG Bid. Kurikulum</p>
        <p class="text-[11px] leading-tight text-slate-500">Karawang Timur</p>
    </div>
</div>

<nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-3" aria-label="Navigasi utama">
    @php
        $navigation = [
            ['label' => 'Dashboard', 'permission' => 'view-dashboard', 'route' => 'dashboard', 'icon' => '◈'],
            ['label' => 'Manajemen Pengguna', 'permission' => 'manage-users', 'icon' => '⚙', 'items' => [
                ['label' => 'Data Pengguna', 'route' => 'users.index'],
                ['label' => 'Data Peran', 'route' => 'roles.index'],
                ['label' => 'Data Wajah', 'route' => 'face-enrollment.index'],
            ]],
            ['label' => 'Generus', 'permission' => ['view-generus', 'manage-generus'], 'icon' => '◎', 'items' => [
                ['label' => 'Data Generus', 'route' => 'generus.index'],
                ['label' => 'Tambah Generus', 'route' => 'generus.create', 'permission' => 'manage-generus'],
            ]],
            ['label' => 'Guru', 'permission' => ['view-teachers', 'manage-teachers'], 'icon' => '✎', 'items' => [
                ['label' => 'Data Guru', 'route' => 'teachers.index'],
                ['label' => 'Murid Saya', 'route' => 'my-students.edit', 'permission' => 'manage-my-students'],
            ]],
            ['label' => 'Orang Tua / Wali', 'permission' => ['view-guardians', 'manage-guardians'], 'icon' => '⌂', 'items' => [
                ['label' => 'Data Wali', 'route' => 'guardians.index'],
                ['label' => 'Komunikasi Orang Tua', 'route' => 'parent-communications.index', 'permission' => ['view-parent-communications', 'manage-parent-communications']],
            ]],
            ['label' => 'Organisasi', 'permission' => ['view-organization-units', 'manage-organization-units'], 'icon' => '▣', 'items' => [
                ['label' => 'Struktur Organisasi', 'route' => 'organization-units.index'],
                ['label' => 'Penempatan', 'route' => 'assignments.index', 'permission' => ['view-assignments', 'manage-assignments']],
            ]],
            ['label' => 'Master Data', 'permission' => 'view-master-data', 'icon' => '⌘', 'items' => [
                ['label' => 'Desa', 'route' => 'master-data.villages.index'],
                ['label' => 'Kelompok', 'route' => 'master-data.groups.index'],
                ['label' => 'Jenjang', 'route' => 'master-data.levels.index'],
                ['label' => 'Kelas', 'route' => 'master-data.class-grades.index'],
                ['label' => 'Tahun Akademik', 'route' => 'master-data.academic-years.index'],
                ['label' => 'Semester', 'route' => 'master-data.semesters.index'],
            ]],
            ['label' => 'Kurikulum', 'permission' => ['view-learning-materials', 'manage-learning-materials'], 'icon' => '▥', 'items' => [
                ['label' => 'Kategori Materi', 'route' => 'material-categories.index'],
                ['label' => 'Bab Materi', 'route' => 'material-chapters.index'],
                ['label' => 'Materi', 'route' => 'learning-materials.index'],
                ['label' => 'Progress Tracking', 'route' => 'progress-tracks.index', 'permission' => ['view-progress-tracking', 'manage-progress-tracking']],
            ]],
            ['label' => 'Program Pembinaan', 'permission' => ['view-activity-schedules', 'manage-activity-schedules'], 'icon' => '◷', 'items' => [
                ['label' => 'Jadwal Program', 'route' => 'activity-schedules.index'],
                ['label' => 'Pelaksanaan Program', 'route' => 'activity-executions.index', 'permission' => ['view-activity-executions', 'manage-activity-executions']],
                ['label' => 'Milestone', 'route' => 'milestones.index', 'permission' => ['view-milestones', 'manage-milestones']],
            ]],
            ['label' => 'KBM', 'permission' => ['view-learning-sessions', 'manage-learning-sessions'], 'icon' => '▦', 'items' => [
                ['label' => 'Sesi KBM', 'route' => 'learning-sessions.index'],
                ['label' => 'Absensi Sesi', 'route' => 'session-attendances.index', 'permission' => ['view-learning-attendance', 'manage-learning-attendance']],
            ]],
            ['label' => 'Evaluasi', 'permission' => ['view-evaluations', 'manage-evaluations'], 'icon' => '✓', 'items' => [
                ['label' => 'Evaluasi', 'route' => 'evaluations.index'],
                ['label' => 'Nilai Evaluasi', 'route' => 'evaluation-scores.index'],
            ]],
            ['label' => 'Munaqosah', 'permission' => ['view-munaqosah', 'manage-munaqosah'], 'icon' => '✦', 'items' => [
                ['label' => 'Daftar Munaqosah', 'route' => 'munaqosahs.index'],
            ]],
            ['label' => 'Rapor', 'permission' => ['view-report-cards', 'manage-report-cards'], 'icon' => '▧', 'items' => [
                ['label' => 'Rapor Generus', 'route' => 'report-cards.index'],
            ]],
            ['label' => 'Pelatihan Guru', 'permission' => ['view-training', 'manage-training'], 'icon' => '⚡', 'items' => [
                ['label' => 'Program Pelatihan', 'route' => 'trainings.index'],
            ]],
            ['label' => 'Komunikasi', 'permission' => ['view-communication', 'manage-communication'], 'icon' => '✉', 'items' => [
                ['label' => 'Pesan dan Riwayat', 'route' => 'communications.index'],
            ]],
            ['label' => 'Berita', 'permission' => ['view-announcements', 'manage-announcements'], 'route' => 'announcements.index', 'icon' => '❖'],
            ['label' => 'Laporan', 'permission' => 'view-reports', 'route' => 'reports.index', 'icon' => '▤'],
            ['label' => 'Tutorial', 'route' => 'tutorial', 'icon' => '?'],
        ];
    @endphp

    @foreach ($navigation as $section)
        @php
            $sectionPermission = $section['permission'] ?? null;
            $sectionAllowed = ! $sectionPermission || \App\Support\Access::can(...\Illuminate\Support\Arr::wrap($sectionPermission));
            $visibleItems = collect($section['items'] ?? [])->filter(fn ($item) => ! isset($item['permission']) || \App\Support\Access::can(...\Illuminate\Support\Arr::wrap($item['permission'])));
            $isActive = isset($section['route'])
                ? request()->routeIs($section['route'])
                : $visibleItems->contains(fn ($item) => request()->routeIs($item['route']));
        @endphp

        @if ($sectionAllowed && (isset($section['route']) || $visibleItems->isNotEmpty()))
            @if (isset($section['route']))
                <a href="{{ route($section['route']) }}" class="flex items-center gap-2.5 rounded-md border-l-2 px-2.5 py-2 text-[13px] font-medium transition {{ $isActive ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <span class="w-4 text-center text-sm">{{ $section['icon'] ?? '•' }}</span>
                    <span>{{ $section['label'] }}</span>
                </a>
            @else
                <details class="group" @if ($isActive) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between rounded-md border-l-2 px-2.5 py-2 text-[13px] font-medium transition [&::-webkit-details-marker]:hidden {{ $isActive ? 'border-amber-500 text-slate-900 hover:bg-slate-100' : 'border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span class="flex items-center gap-2.5">
                            <span class="w-4 text-center text-sm">{{ $section['icon'] ?? '•' }}</span>
                            <span>{{ $section['label'] }}</span>
                        </span>
                        <span class="text-[9px] text-slate-400 transition-transform duration-200 group-open:rotate-180">▾</span>
                    </summary>
                    <div class="space-y-0.5 py-0.5 pl-6">
                        @foreach ($visibleItems as $item)
                            <a href="{{ route($item['route']) }}" class="block rounded-md border-l-2 px-2.5 py-1.5 text-[13px] transition {{ request()->routeIs($item['route']) ? 'border-amber-500 bg-amber-50 font-semibold text-amber-700 hover:bg-amber-100' : 'border-transparent text-slate-500 hover:bg-slate-100 hover:text-slate-900' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        @endif
    @endforeach
</nav>