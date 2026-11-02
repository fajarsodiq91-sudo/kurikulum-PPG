@extends('layouts.app')

@section('title', 'Jadwal KBM Default')
@section('page-title', 'Jadwal KBM Default')
@section('breadcrumb', 'KBM / Jadwal KBM Default')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">KBM</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Jadwal KBM Default</h2>
        <p class="mt-2 text-sm text-slate-500">Pola hari &amp; jam KBM default per Jenjang untuk satu semester. Tanggal tertentu bisa diliburkan — kelompok tetap boleh melaksanakan KBM di luar jadwal ini.</p>
    </div>

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('default-schedules.index') }}" class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium mb-2" for="level_id">Jenjang</label>
                <select id="level_id" name="level_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected($levelId == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun Ajaran</label>
                <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId == $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="semester_id">Semester</label>
                <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($semesterId == $semester->id)>{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Tampilkan</button>
            </div>
        </form>
    </section>

    @if ($levelId && $academicYearId && $semesterId)
        @if (! \App\Support\Access::can('manage-learning-sessions'))
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Pola Jadwal</h3>
                @if ($schedule)
                    <p class="mt-2 text-sm text-slate-600">
                        {{ collect($schedule->days_of_week)->map(fn ($d) => $days[$d])->join(', ') }}
                        @if ($schedule->start_time) · {{ $schedule->start_time }}–{{ $schedule->end_time }} @endif
                        · {{ $schedule->start_date->translatedFormat('d M Y') }} s/d {{ $schedule->end_date->translatedFormat('d M Y') }}
                    </p>
                @else
                    <p class="mt-2 text-sm text-slate-500">Belum ada pola jadwal untuk Jenjang, Tahun Ajaran, dan Semester ini.</p>
                @endif
            </section>
        @else
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Pola Jadwal</h3>
            <form method="POST" action="{{ route('default-schedules.update') }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="level_id" value="{{ $levelId }}">
                <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                <input type="hidden" name="semester_id" value="{{ $semesterId }}">

                <div>
                    <span class="block text-sm font-medium mb-2">Hari</span>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($days as $value => $label)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="days_of_week[]" value="{{ $value }}" @checked($schedule && in_array($value, $schedule->days_of_week ?? [], true)) class="rounded border-slate-300">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-2" for="start_date">Berlaku Mulai</label>
                        <input id="start_date" name="start_date" type="date" required value="{{ $schedule?->start_date?->toDateString() }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" for="end_date">Berlaku Sampai</label>
                        <input id="end_date" name="end_date" type="date" required value="{{ $schedule?->end_date?->toDateString() }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" for="start_time">Jam Mulai</label>
                        <input id="start_time" name="start_time" type="time" value="{{ $schedule?->start_time }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" for="end_time">Jam Selesai</label>
                        <input id="end_time" name="end_time" type="time" value="{{ $schedule?->end_time }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
            </form>
        </section>

        @if ($schedule)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Hari Libur</h3>
                <p class="mt-1 text-sm text-slate-500">Tanggal yang ditandai libur tidak akan dianggap jadwal KBM default, meski jatuh pada hari yang dipilih di atas.</p>

                <form method="POST" action="{{ route('default-schedules.exceptions.store', $schedule) }}" class="mt-4 flex flex-wrap items-end gap-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-2" for="exception_date">Tanggal</label>
                        <input id="exception_date" name="exception_date" type="date" required class="rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2" for="reason">Keterangan (opsional)</label>
                        <input id="reason" name="reason" type="text" class="rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-500">Tambah Libur</button>
                </form>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Tanggal</th>
                                <th class="py-3 pr-4">Keterangan</th>
                                <th class="py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($schedule->exceptions->sortBy('exception_date') as $exception)
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-4">{{ $exception->exception_date->translatedFormat('d F Y') }}</td>
                                    <td class="py-2 pr-4">{{ $exception->reason ?? '-' }}</td>
                                    <td class="py-2 text-right">
                                        <form method="POST" action="{{ route('default-schedules.exceptions.destroy', $exception) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada hari libur.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
        @endif
    @endif
@endsection
