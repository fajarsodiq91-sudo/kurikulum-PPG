<div class="flex h-20 shrink-0 items-center gap-3 border-b border-slate-800 px-6">
    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400 font-black text-slate-950">PPG</div>
    <div>
        <p class="text-sm font-bold tracking-wide text-white">PPG Management</p>
        <p class="text-xs text-slate-500">Karawang Timur</p>
    </div>
</div>

<nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6" aria-label="Navigasi utama">
    @php
        $navigation = [
            ['label' => 'Dashboard', 'permission' => 'view-dashboard', 'route' => 'dashboard', 'icon' => '◈'],
            ['label' => 'Generus', 'permission' => 'manage-generus', 'items' => [
                ['label' => 'Data Generus', 'route' => 'generus.index'],
                ['label' => 'Tambah Generus', 'route' => 'generus.create'],
            ]],
            ['label' => 'Guru', 'permission' => 'manage-teachers', 'items' => [
                ['label' => 'Data Guru', 'route' => 'teachers.index'],
            ]],
            ['label' => 'Orang Tua / Wali', 'permission' => 'manage-guardians', 'items' => [
                ['label' => 'Data Wali', 'route' => 'guardians.index'],
            ]],
            ['label' => 'Organisasi', 'permission' => 'manage-organization-units', 'items' => [
                ['label' => 'Struktur Organisasi', 'route' => 'organization-units.index'],
                ['label' => 'Penempatan', 'route' => 'assignments.index', 'permission' => 'manage-assignments'],
            ]],
            ['label' => 'Master Data', 'permission' => 'view-master-data', 'items' => [
                ['label' => 'Daerah', 'route' => 'master-data.regions.index'],
            ]],
            ['label' => 'Kurikulum', 'permission' => 'manage-curriculum', 'items' => [
                ['label' => 'Program Kurikulum', 'route' => 'curriculum-programs.index'],
                ['label' => 'Materi', 'route' => 'learning-materials.index', 'permission' => 'manage-learning-materials'],
                ['label' => 'Progress Tracking', 'route' => 'progress-tracks.index', 'permission' => 'manage-progress-tracking'],
            ]],
            ['label' => 'Program Pembinaan', 'permission' => 'manage-activity-schedules', 'items' => [
                ['label' => 'Jadwal Program', 'route' => 'activity-schedules.index'],
                ['label' => 'Pelaksanaan Program', 'route' => 'activity-executions.index', 'permission' => 'manage-activity-executions'],
                ['label' => 'Milestone', 'route' => 'milestones.index', 'permission' => 'manage-milestones'],
            ]],
            ['label' => 'KBM', 'permission' => 'manage-learning-sessions', 'items' => [
                ['label' => 'Sesi KBM', 'route' => 'learning-sessions.index'],
                ['label' => 'Absensi Sesi', 'route' => 'session-attendances.index', 'permission' => 'manage-learning-attendance'],
            ]],
            ['label' => 'Evaluasi', 'permission' => 'manage-evaluations', 'items' => [
                ['label' => 'Evaluasi', 'route' => 'evaluations.index'],
                ['label' => 'Nilai Evaluasi', 'route' => 'evaluation-scores.index'],
            ]],
            ['label' => 'Munaqosah', 'permission' => 'manage-munaqosah', 'items' => [
                ['label' => 'Daftar Munaqosah', 'route' => 'munaqosahs.index'],
            ]],
            ['label' => 'Rapor', 'permission' => 'manage-report-cards', 'items' => [
                ['label' => 'Rapor Generus', 'route' => 'report-cards.index'],
            ]],
            ['label' => 'Pelatihan Guru', 'permission' => 'manage-training', 'items' => [
                ['label' => 'Program Pelatihan', 'route' => 'trainings.index'],
            ]],
            ['label' => 'Komunikasi', 'permission' => 'manage-communication', 'items' => [
                ['label' => 'Pesan dan Riwayat', 'route' => 'communications.index'],
            ]],
            ['label' => 'Laporan', 'permission' => 'view-reports', 'route' => 'reports.index', 'icon' => '▤'],
        ];
    @endphp

    @foreach ($navigation as $section)
        @php
            $sectionPermission = $section['permission'] ?? null;
            $sectionAllowed = ! $sectionPermission || auth()->user()->hasPermission($sectionPermission);
            $visibleItems = collect($section['items'] ?? [])->filter(fn ($item) => ! isset($item['permission']) || auth()->user()->hasPermission($item['permission']));
            $isActive = isset($section['route'])
                ? request()->routeIs($section['route'])
                : $visibleItems->contains(fn ($item) => request()->routeIs($item['route']));
        @endphp

        @if ($sectionAllowed && (isset($section['route']) || $visibleItems->isNotEmpty()))
            @if (isset($section['route']))
                <a href="{{ route($section['route']) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $isActive ? 'bg-amber-400 text-slate-950' : 'text-slate-400 hover:bg-slate-900 hover:text-white' }}">
                    <span class="w-5 text-center text-base">{{ $section['icon'] ?? '•' }}</span>
                    <span>{{ $section['label'] }}</span>
                </a>
            @else
                <div>
                    <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-600">{{ $section['label'] }}</p>
                    <div class="space-y-1">
                        @foreach ($visibleItems as $item)
                            <a href="{{ route($item['route']) }}" class="block rounded-lg px-3 py-2 text-sm transition {{ request()->routeIs($item['route']) ? 'bg-slate-800 font-semibold text-white' : 'text-slate-400 hover:bg-slate-900 hover:text-white' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    @endforeach
</nav>

<div class="border-t border-slate-800 p-4">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-400 transition hover:bg-slate-900 hover:text-white">Keluar dari sistem</button>
    </form>
</div>