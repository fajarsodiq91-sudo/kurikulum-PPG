@extends('layouts.app')

@section('title', 'Jadwal Munaqosah Semester')
@section('page-title', 'Jadwal Munaqosah Semester')
@section('breadcrumb', 'Munaqosah / Jadwal Semester')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">PPG Daerah</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Jadwal Munaqosah Semester</h2>
        <p class="mt-2 text-sm text-slate-500">Buka jadwal munaqosah semester per kelas KBM: kapan dilaksanakan dan materi apa saja yang diujikan. Kelompok hanya bisa mengisi nilai munaqosah semester untuk kelas yang jadwalnya sudah dibuka di sini.</p>
    </div>

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('munaqosah-schedules.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun Ajaran</label>
                <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}" @selected($academicYearId === $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2" for="semester_id">Semester</label>
                <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach ($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($semesterId === $semester->id)>{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Tampilkan</button>
        </form>
    </section>

    @if ($academicYearId && $semesterId)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Per Kelas KBM</h3>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
            @endif

            <div class="mt-4 divide-y divide-slate-100">
                @foreach ($classGrades as $classGrade)
                    @php($schedule = $schedules->get($classGrade->id))
                    <details class="py-4" @if ($schedule) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between">
                            <span class="font-semibold text-slate-900">{{ $classGrade->level->name }} — {{ $classGrade->name }}</span>
                            @if ($schedule?->is_open)
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Dibuka</span>
                            @elseif ($schedule)
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Ditutup</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-400">Belum diatur</span>
                            @endif
                        </summary>

                        <form method="POST" action="{{ route('munaqosah-schedules.store') }}" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="class_grade_id" value="{{ $classGrade->id }}">
                            <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                            <input type="hidden" name="semester_id" value="{{ $semesterId }}">
                            <input type="hidden" name="type" value="semester-final">

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Materi Munaqosah</label>
                                <textarea name="materials" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('materials', $schedule?->materials) }}</textarea>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Mulai</label>
                                    <input type="date" name="scheduled_start" value="{{ old('scheduled_start', $schedule?->scheduled_start?->toDateString()) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-slate-700">Selesai</label>
                                    <input type="date" name="scheduled_end" value="{{ old('scheduled_end', $schedule?->scheduled_end?->toDateString()) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </div>
                            </div>

                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_open" value="1" @checked(old('is_open', $schedule?->is_open)) class="rounded border-slate-300">
                                Buka untuk diisi kelompok sekarang
                            </label>

                            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>
    @endif
@endsection
