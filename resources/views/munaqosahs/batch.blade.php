@extends('layouts.app')

@section('title', 'Input Nilai Munaqosah per Kelompok')
@section('page-title', 'Input Nilai Munaqosah per Kelompok')
@section('breadcrumb', 'Munaqosah / Input per Kelompok')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Munaqosah</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Input Nilai per Kelompok</h2>
            <p class="mt-2 text-sm text-slate-500">Pilih kelompok, tahun ajaran, dan semester, lalu isi nilai seluruh generus sekaligus.</p>
        </div>
        <a href="{{ route('munaqosahs.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke daftar</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    @include('layouts.partials.validation-errors')

    <section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('munaqosahs.batch') }}" class="grid gap-3 sm:grid-cols-4">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="group_id">Kelompok</label>
                <select id="group_id" name="group_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach($groups as $item)
                        <option value="{{ $item->id }}" @selected($group?->id === $item->id)>{{ $item->village?->name ? $item->village->name.' - ' : '' }}{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="academic_year_id">Tahun</label>
                <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected($academicYearId === $year->id)>{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="semester_id">Semester</label>
                <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" @selected($semesterId === $semester->id)>{{ $semester->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="type">Tipe</label>
                <select id="type" name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="semester-final" @selected($type === 'semester-final')>Semester Final</option>
                    <option value="remedial" @selected($type === 'remedial')>Remedial</option>
                </select>
            </div>
            <div class="sm:col-span-4">
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Tampilkan Kelompok</button>
            </div>
        </form>
    </section>

    @if ($group)
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('munaqosahs.batch.save') }}">
                @csrf
                <input type="hidden" name="group_id" value="{{ $group->id }}">
                <input type="hidden" name="academic_year_id" value="{{ $academicYearId }}">
                <input type="hidden" name="semester_id" value="{{ $semesterId }}">
                <input type="hidden" name="type" value="{{ $type }}">

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="title">Judul Munaqosah</label>
                    <input id="title" name="title" type="text" required value="{{ old('title', 'Munaqosah '.($semesters->firstWhere('id', $semesterId)?->name ?? '')) }}" class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Generus</th>
                                <th class="py-3 pr-4">Nilai</th>
                                <th class="py-3 pr-4">Hasil</th>
                                <th class="py-3 pr-4">Status</th>
                                <th class="py-3">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roster as $item)
                                @php($recorded = $existing[$item->id] ?? null)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4 font-medium text-slate-900">{{ $item->full_name }}</td>
                                    <td class="py-3 pr-4">
                                        <input type="number" step="0.01" min="0" max="100" name="scores[{{ $item->id }}][score]" value="{{ old("scores.{$item->id}.score", $recorded?->score) }}" class="w-24 rounded-lg border border-slate-300 px-2 py-1.5">
                                    </td>
                                    <td class="py-3 pr-4">
                                        <input type="text" name="scores[{{ $item->id }}][result]" value="{{ old("scores.{$item->id}.result", $recorded?->result) }}" placeholder="Otomatis" class="w-20 rounded-lg border border-slate-300 px-2 py-1.5">
                                    </td>
                                    <td class="py-3 pr-4">
                                        <select name="scores[{{ $item->id }}][status]" class="rounded-lg border border-slate-300 px-2 py-1.5">
                                            @php($currentStatus = old("scores.{$item->id}.status", $recorded?->status ?? 'completed'))
                                            <option value="scheduled" @selected($currentStatus === 'scheduled')>Terjadwal</option>
                                            <option value="completed" @selected($currentStatus === 'completed')>Selesai</option>
                                            <option value="failed" @selected($currentStatus === 'failed')>Gagal</option>
                                        </select>
                                    </td>
                                    <td class="py-3">
                                        <input type="text" name="scores[{{ $item->id }}][notes]" value="{{ old("scores.{$item->id}.notes", $recorded?->notes) }}" class="w-full min-w-40 rounded-lg border border-slate-300 px-2 py-1.5">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-slate-500">Tidak ada generus aktif di kelompok ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($roster->isNotEmpty())
                    <p class="mt-4 text-xs text-slate-500">Baris tanpa nilai dan tanpa hasil tidak disimpan. Kosongkan kolom hasil agar huruf dan keterangan terisi otomatis dari master data Konversi Nilai.</p>
                    <button type="submit" class="mt-4 w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 sm:w-auto">Simpan Nilai Kelompok</button>
                @endif
            </form>
        </section>
    @endif
@endsection
