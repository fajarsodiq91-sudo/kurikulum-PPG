@extends('layouts.app')

@section('title', 'Jadwal KBM')
@section('page-title', 'Jadwal KBM')
@section('breadcrumb', 'KBM / Sesi KBM')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">KBM</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Jadwal KBM</h2>
            <p class="mt-2 text-sm text-slate-500">Kelola sesi pembelajaran, pengajar, dan waktu pelaksanaan.</p>
        </div>
        @if (\App\Support\Access::can('manage-learning-sessions'))
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('learning-sessions.generate') }}" class="inline-flex items-center justify-center rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Generate Jadwal Mingguan</a>
            </div>
        @endif
    </div>

        @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('learning-sessions.export')])

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            @if (\App\Support\Access::can('manage-learning-sessions'))
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Sesi</h2>
                <form method="POST" action="{{ route('learning-sessions.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="teacher_id">Guru</label>
                        <select id="teacher_id" name="teacher_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="material_id">Materi</label>
                        <select id="material_id" name="material_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="level">Tingkat Pelaksanaan</label>
                        <select id="level" name="level" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            <option value="village" @selected(old('level') === 'village')>Desa</option>
                            <option value="group" @selected(old('level') === 'group')>Kelompok</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Daftar hadir sesi desa diisi Perwakilan PPG Desa, sesi kelompok diisi Pelaksana PPG Kelompok.</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="village_id">Desa</label>
                        <select id="village_id" name="village_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih (untuk tingkat desa) --</option>
                            @foreach($villages as $village)
                                <option value="{{ $village->id }}" @selected(old('village_id') == $village->id)>{{ $village->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="group_id">Kelompok</label>
                        <select id="group_id" name="group_id" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih (untuk tingkat kelompok) --</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="session_date">Tanggal</label>
                        <input id="session_date" name="session_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="start_time">Jam Mulai</label>
                            <input id="start_time" name="start_time" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="end_time">Jam Selesai</label>
                            <input id="end_time" name="end_time" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="location">Lokasi</label>
                        <input id="location" name="location" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="scheduled">Terjadwal</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </form>
            </div>
            @endif

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Sesi</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Tanggal</th>
                                <th class="py-3 pr-4">Guru</th>
                                <th class="py-3 pr-4">Materi</th>
                                <th class="py-3 pr-4">Kelas KBM</th>
                                <th class="py-3 pr-4">Tingkat</th>
                                <th class="py-3 pr-4">Status</th>
                                <th class="py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                @php($isCompletable = $canManageAllSessions || in_array($session->id, $completableIds ?? [], true))
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $session->session_date }}</td>
                                    <td class="py-3 pr-4">
                                        {{ $session->teacher?->name ?? '-' }}
                                        @if (! $session->teacher_id)
                                            <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Belum dilengkapi</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4">{{ $session->material?->title ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $session->classGrade?->name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $session->levelLabel() }}</td>
                                    <td class="py-3 pr-4">{{ $session->status }}</td>
                                    <td class="py-3 text-right whitespace-nowrap">
                                        @if ($isCompletable)
                                            <a href="{{ route('learning-sessions.edit', $session) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Lengkapi</a>
                                        @endif
                                        @if (in_array($session->id, $attendableIds, true))
                                            <a href="{{ route('learning-sessions.attendance.edit', $session) }}" class="ml-3 text-xs font-semibold text-amber-600 hover:text-amber-700">Daftar Hadir</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-slate-500">Belum ada sesi KBM.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $sessions->links() }}</div>
            </div>
        </div>
    </div>
@endsection
