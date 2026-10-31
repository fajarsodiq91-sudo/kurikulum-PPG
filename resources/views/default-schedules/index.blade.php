@extends('layouts.app')

@section('title', 'Jadwal KBM Default')
@section('page-title', 'Jadwal KBM Default')
@section('breadcrumb', 'KBM / Jadwal KBM Default')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">KBM</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Jadwal KBM Default</h2>
        <p class="mt-2 text-sm text-slate-500">Referensi kalender mingguan per Jenjang untuk satu semester. Tanggal/hari/jam bersifat opsional — kelompok tetap boleh melaksanakan KBM di luar jadwal ini.</p>
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
        @if (\App\Support\Access::can('manage-learning-sessions'))
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Buat Minggu</h3>
                <form method="POST" action="{{ route('default-schedules.generate') }}" class="mt-4 flex flex-wrap items-end gap-4">
                    @csrf
                    <input type="hidden" name="level_id" value="{{ $levelId }}">
                    <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                    <input type="hidden" name="semester_id" value="{{ $semesterId }}">
                    <div>
                        <label class="block text-sm font-medium mb-2" for="week_count">Jumlah Minggu</label>
                        <input id="week_count" name="week_count" type="number" min="1" max="52" value="20" required class="w-32 rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-500">Generate Minggu 1–N</button>
                </form>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if ($weeks->isEmpty())
                <p class="py-10 text-center text-sm text-slate-500">Belum ada minggu untuk Jenjang, Tahun Ajaran, dan Semester ini.</p>
            @else
                <form method="POST" action="{{ route('default-schedules.update') }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="level_id" value="{{ $levelId }}">
                    <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                    <input type="hidden" name="semester_id" value="{{ $semesterId }}">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="py-3 pr-4">Minggu</th>
                                    <th class="py-3 pr-4">Tanggal</th>
                                    <th class="py-3 pr-4">Hari</th>
                                    <th class="py-3 pr-4">Jam Mulai</th>
                                    <th class="py-3 pr-4">Jam Selesai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($weeks as $week)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-2 pr-4 font-medium text-slate-900">Minggu {{ $week->week_number }}</td>
                                        <td class="py-2 pr-4"><input type="date" name="weeks[{{ $week->id }}][scheduled_date]" value="{{ $week->scheduled_date }}" class="rounded-lg border border-slate-300 px-2 py-1"></td>
                                        <td class="py-2 pr-4">
                                            <select name="weeks[{{ $week->id }}][day_of_week]" class="rounded-lg border border-slate-300 px-2 py-1">
                                                <option value="">-- Pilih --</option>
                                                @foreach ($days as $value => $label)
                                                    <option value="{{ $value }}" @selected($week->day_of_week == $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="py-2 pr-4"><input type="time" name="weeks[{{ $week->id }}][start_time]" value="{{ $week->start_time }}" class="rounded-lg border border-slate-300 px-2 py-1"></td>
                                        <td class="py-2 pr-4"><input type="time" name="weeks[{{ $week->id }}][end_time]" value="{{ $week->end_time }}" class="rounded-lg border border-slate-300 px-2 py-1"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (\App\Support\Access::can('manage-learning-sessions'))
                        <button type="submit" class="mt-4 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                    @endif
                </form>
            @endif
        </section>
    @endif
@endsection
