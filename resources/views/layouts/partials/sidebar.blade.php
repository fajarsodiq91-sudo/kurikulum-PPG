<div class="flex h-14 shrink-0 items-center gap-2.5 border-b border-slate-800 px-4">
    <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-9 w-9 shrink-0 object-contain">
    <div>
        <p class="text-xs font-bold leading-tight tracking-wide text-white">PPG Management</p>
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
            ]],
            ['label' => 'Generus', 'permission' => 'manage-generus', 'icon' => '◎', 'items' => [
                ['label' => 'Data Generus', 'route' => 'generus.index'],
                ['label' => 'Tambah Generus', 'route' => 'generus.create'],
            ]],
            ['label' => 'Guru', 'permission' => 'manage-teachers', 'icon' => '✎', 'items' => [
                ['label' => 'Data Guru', 'route' => 'teachers.index'],
            ]],
            ['label' => 'Orang Tua / Wali', 'permission' => 'manage-guardians', 'icon' => '⌂', 'items' => [
                ['label' => 'Data Wali', 'route' => 'guardians.index'],
            ]],
            ['label' => 'Organisasi', 'permission' => 'manage-organization-units', 'icon' => '▣', 'items' => [
                ['label' => 'Struktur Organisasi', 'route' => 'organization-units.index'],
                ['label' => 'Penempatan', 'route' => 'assignments.index', 'permission' => 'manage-assignments'],
            ]],
            ['label' => 'Master Data', 'permission' => 'view-master-data', 'icon' => '⌘', 'items' => [
                ['label' => 'Semua Master Data', 'route' => 'master-data.index'],
                ['label' => 'Daerah', 'route' => 'master-data.regions.index'],
            ]],
            ['label' => 'Kurikulum', 'permission' => 'manage-curriculum', 'icon' => '▥', 'items' => [
                ['label' => 'Program Kurikulum', 'route' => 'curriculum-programs.index'],
                ['label' => 'Materi', 'route' => 'learning-materials.index', 'permission' => 'manage-learning-materials'],
                ['label' => 'Progress Tracking', 'route' => 'progress-tracks.index', 'permission' => 'manage-progress-tracking'],
            ]],
            ['label' => 'Program Pembinaan', 'permission' => 'manage-activity-schedules', 'icon' => '◷', 'items' => [
                ['label' => 'Jadwal Program', 'route' => 'activity-schedules.index'],
                ['label' => 'Pelaksanaan Program', 'route' => 'activity-executions.index', 'permission' => 'manage-activity-executions'],
                ['label' => 'Milestone', 'route' => 'milestones.index', 'permission' => 'manage-milestones'],
            ]],
            ['label' => 'KBM', 'permission' => 'manage-learning-sessions', 'icon' => '▦', 'items' => [
                ['label' => 'Sesi KBM', 'route' => 'learning-sessions.index'],
                ['label' => 'Absensi Sesi', 'route' => 'session-attendances.index', 'permission' => 'manage-learning-attendance'],
            ]],
            ['label' => 'Evaluasi', 'permission' => 'manage-evaluations', 'icon' => '✓', 'items' => [
                ['label' => 'Evaluasi', 'route' => 'evaluations.index'],
                ['label' => 'Nilai Evaluasi', 'route' => 'evaluation-scores.index'],
            ]],
            ['label' => 'Munaqosah', 'permission' => 'manage-munaqosah', 'icon' => '✦', 'items' => [
                ['label' => 'Daftar Munaqosah', 'route' => 'munaqosahs.index'],
            ]],
            ['label' => 'Rapor', 'permission' => 'manage-report-cards', 'icon' => '▧', 'items' => [
                ['label' => 'Rapor Generus', 'route' => 'report-cards.index'],
            ]],
            ['label' => 'Pelatihan Guru', 'permission' => 'manage-training', 'icon' => '⚡', 'items' => [
                ['label' => 'Program Pelatihan', 'route' => 'trainings.index'],
            ]],
            ['label' => 'Komunikasi', 'permission' => 'manage-communication', 'icon' => '✉', 'items' => [
                ['label' => 'Pesan dan Riwayat', 'route' => 'communications.index'],
            ]],
            ['label' => 'Laporan', 'permission' => 'view-reports', 'route' => 'reports.index', 'icon' => '▤'],
        ];
    @endphp

    @foreach ($navigation as $section)
        @php
            $sectionPermission = $section['permission'] ?? null;
            $sectionAllowed = ! $sectionPermission || \App\Support\Access::can($sectionPermission);
            $visibleItems = collect($section['items'] ?? [])->filter(fn ($item) => ! isset($item['permission']) || \App\Support\Access::can($item['permission']));
            $isActive = isset($section['route'])
                ? request()->routeIs($section['route'])
                : $visibleItems->contains(fn ($item) => request()->routeIs($item['route']));
        @endphp

        @if ($sectionAllowed && (isset($section['route']) || $visibleItems->isNotEmpty()))
            @if (isset($section['route']))
                <a href="{{ route($section['route']) }}" class="flex items-center gap-2.5 rounded-md border-l-2 px-2.5 py-2 text-[13px] font-medium transition {{ $isActive ? 'border-amber-400 bg-amber-400 text-slate-950' : 'border-transparent text-slate-400 hover:bg-slate-900 hover:text-white' }}">
                    <span class="w-4 text-center text-sm">{{ $section['icon'] ?? '•' }}</span>
                    <span>{{ $section['label'] }}</span>
                </a>
            @else
                <details class="group" @if ($isActive) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between rounded-md border-l-2 px-2.5 py-2 text-[13px] font-medium transition [&::-webkit-details-marker]:hidden {{ $isActive ? 'border-amber-400 text-white' : 'border-transparent text-slate-400 hover:bg-slate-900 hover:text-white' }}">
                        <span class="flex items-center gap-2.5">
                            <span class="w-4 text-center text-sm">{{ $section['icon'] ?? '•' }}</span>
                            <span>{{ $section['label'] }}</span>
                        </span>
                        <span class="text-[9px] text-slate-500 transition-transform duration-200 group-open:rotate-180">▾</span>
                    </summary>
                    <div class="space-y-0.5 py-0.5 pl-6">
                        @foreach ($visibleItems as $item)
                            <a href="{{ route($item['route']) }}" class="block rounded-md border-l-2 px-2.5 py-1.5 text-[13px] transition {{ request()->routeIs($item['route']) ? 'border-amber-400 bg-slate-800 font-semibold text-white' : 'border-transparent text-slate-400 hover:bg-slate-900 hover:text-white' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </details>
            @endif
        @endif
    @endforeach
</nav>