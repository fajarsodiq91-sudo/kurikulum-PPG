@php
    $permissionLabels = [
        'view-dashboard' => 'Lihat Dasbor',
        'view-reports' => 'Lihat Laporan',
        'view-master-data' => 'Lihat Data Master',
        'manage-users' => 'Kelola Pengguna',
        'manage-generus' => 'Kelola Generus',
        'manage-teachers' => 'Kelola Guru',
        'manage-guardians' => 'Kelola Wali',
        'manage-curriculum' => 'Kelola Kurikulum',
        'manage-learning-materials' => 'Kelola Materi Pembelajaran',
        'manage-learning-sessions' => 'Kelola Sesi Pembelajaran',
        'manage-learning-attendance' => 'Kelola Absensi Pembelajaran',
        'manage-evaluations' => 'Kelola Evaluasi',
        'manage-training' => 'Kelola Pelatihan',
        'manage-communication' => 'Kelola Komunikasi',
        'manage-munaqosah' => 'Kelola Munaqosah',
        'manage-report-cards' => 'Kelola Rapor',
        'manage-follow-ups' => 'Kelola Tindak Lanjut',
        'manage-progress-tracking' => 'Kelola Pelacakan Progres',
        'manage-milestones' => 'Kelola Milestone',
        'manage-annual-audit' => 'Kelola Audit Tahunan',
        'manage-organization-units' => 'Kelola Struktur Organisasi',
        'manage-assignments' => 'Kelola Penempatan',
        'manage-activity-schedules' => 'Kelola Jadwal Kegiatan',
        'manage-activity-executions' => 'Kelola Pelaksanaan Kegiatan',
    ];
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
        <div class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
            @foreach ($permissions as $permission)
                <label class="flex items-center gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', $role?->permissions->pluck('id')->all() ?? []))) class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                    <span>{{ $permissionLabels[$permission->slug] ?? $permission->name }} <span class="text-slate-400">({{ $permission->slug }})</span></span>
                </label>
            @endforeach
        </div>
    </div>
</div>
