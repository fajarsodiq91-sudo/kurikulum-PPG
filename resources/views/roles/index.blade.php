@extends('layouts.app')

@section('title', 'Data Peran')
@section('page-title', 'Data Peran')
@section('breadcrumb', 'Manajemen Pengguna / Data Peran')

@section('content')
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

    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Manajemen Pengguna</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Data Peran</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola grup peran dan izin akses pengguna.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @include('layouts.partials.validation-errors')

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Tambah Peran</h2>
            <p class="mt-1 text-sm text-slate-500">Buat kelompok hak akses baru.</p>

            <form class="mt-6" method="POST" action="{{ route('roles.store') }}">
                @csrf
                @include('roles.partials.fields', ['role' => null])

                <button type="submit" class="mt-6 w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Simpan Peran
                </button>
            </form>
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Daftar Peran</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $roles->total() }} peran terdaftar.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-3 pr-4 font-semibold">Nama</th>
                            <th class="py-3 pr-4 font-semibold">Kode</th>
                            <th class="py-3 pr-4 font-semibold">Status</th>
                            <th class="py-3 pr-4 font-semibold">Hak Akses</th>
                            <th class="py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 pr-4">{{ $role->name }}</td>
                                <td class="py-3 pr-4 text-slate-500" title="Kode teknis peran">{{ $role->slug }}</td>
                                <td class="py-3 pr-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $role->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $role->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4">
                                    @forelse ($role->permissions as $permission)
                                        <span class="mr-1 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-700">{{ $permissionLabels[$permission->slug] ?? $permission->name }}</span>
                                    @empty
                                        <span class="text-slate-400">-</span>
                                    @endforelse
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('roles.edit', $role) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Ubah</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center">
                                    <p class="font-semibold text-slate-700">Belum ada data peran</p>
                                    <p class="mt-1 text-sm text-slate-500">Buat peran pertama untuk mengatur hak akses sistem.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $roles->links() }}</div>
        </section>
    </div>
@endsection
