@extends('layouts.app')

@section('title', 'Jadwal KBM Default')
@section('page-title', 'Jadwal KBM Default')
@section('breadcrumb', 'KBM / Jadwal KBM Default')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">KBM</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Jadwal KBM Default</h2>
        <p class="mt-2 text-sm text-slate-500">Pola hari &amp; jam KBM default per Jenjang. Tahun ajaran &amp; semester terdeteksi otomatis dari tanggal mulai yang Anda isi, berdasarkan periode semester yang diatur di Master Data &gt; Semester. Tanggal tertentu bisa diliburkan — kelompok tetap boleh melaksanakan KBM di luar jadwal ini.</p>
    </div>

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('default-schedules.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium mb-2" for="level_id">Jenjang</label>
                <select id="level_id" name="level_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected($levelId == $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Tampilkan</button>
        </form>
    </section>

    @if ($levelId)
        @if (\App\Support\Access::can('manage-learning-sessions'))
            <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Tambah / Perbarui Pola Jadwal</h3>
                <p class="mt-1 text-sm text-slate-500">Jika tanggal mulai jatuh pada periode semester yang sudah punya pola, pola tersebut akan diperbarui.</p>

                @if ($errors->any())
                    <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('default-schedules.update') }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="level_id" value="{{ $levelId }}">

                    <div>
                        <span class="block text-sm font-medium mb-2">Hari</span>
                        <div class="flex flex-wrap gap-4">
                            @foreach ($days as $value => $label)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="days_of_week[]" value="{{ $value }}" @checked(collect(old('days_of_week'))->contains($value)) class="rounded border-slate-300">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <span class="block text-sm font-medium mb-2">Klasifikasi Materi (opsional)</span>
                        <p class="mb-2 text-xs text-slate-500">Centang klasifikasi materi yang akan diajarkan pada jadwal ini, supaya di hari H bisa langsung terhubung ke submenu Kontrol Harian terkait.</p>
                        <div class="flex flex-wrap gap-4">
                            @foreach (\App\Http\Controllers\KontrolController::CATEGORIES as $code => $label)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="categories[]" value="{{ $code }}" @checked(collect(old('categories'))->contains($code)) class="rounded border-slate-300">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-2" for="start_date">Berlaku Mulai</label>
                            <input id="start_date" name="start_date" type="date" required value="{{ old('start_date') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" for="end_date">Berlaku Sampai</label>
                            <input id="end_date" name="end_date" type="date" required value="{{ old('end_date') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" for="start_time">Jam Mulai</label>
                            <input id="start_time" name="start_time" type="time" value="{{ old('start_time') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" for="end_time">Jam Selesai</label>
                            <input id="end_time" name="end_time" type="time" value="{{ old('end_time') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                    </div>

                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </form>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Pola Jadwal per Semester</h3>

            @forelse ($schedules as $schedule)
                <details class="mt-4 border-t border-slate-100 pt-4" @if ($loop->first) open @endif>
                    <summary class="cursor-pointer list-none">
                        <span class="font-semibold text-slate-900">{{ $schedule->semester?->name }} / {{ $schedule->academicYear?->name }}</span>
                        <span class="block text-sm text-slate-600">
                            {{ collect($schedule->days_of_week)->map(fn ($d) => $days[$d])->join(', ') }}
                            @if ($schedule->start_time) · {{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }} @endif
                            · {{ $schedule->start_date->translatedFormat('d M Y') }} s/d {{ $schedule->end_date->translatedFormat('d M Y') }}
                            @if (! empty($schedule->categories))
                                · {{ collect($schedule->categories)->map(fn ($c) => \App\Http\Controllers\KontrolController::CATEGORIES[$c] ?? $c)->join(', ') }}
                            @endif
                        </span>
                    </summary>

                    @if (\App\Support\Access::can('manage-learning-sessions'))
                        <div class="mt-4 rounded-xl bg-slate-50 p-4">
                            <h4 class="text-sm font-semibold text-slate-700">Hari Libur</h4>
                            <p class="mt-1 text-xs text-slate-500">Tanggal yang ditandai libur tidak akan dianggap jadwal KBM default, meski jatuh pada hari yang dipilih.</p>

                            <form method="POST" action="{{ route('default-schedules.exceptions.store', $schedule) }}" class="mt-3 flex flex-wrap items-end gap-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium mb-2" for="exception_date_{{ $schedule->id }}">Tanggal</label>
                                    <input id="exception_date_{{ $schedule->id }}" name="exception_date" type="date" required class="rounded-lg border border-slate-300 px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2" for="reason_{{ $schedule->id }}">Keterangan (opsional)</label>
                                    <input id="reason_{{ $schedule->id }}" name="reason" type="text" class="rounded-lg border border-slate-300 px-3 py-2">
                                </div>
                                <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-500">Tambah Libur</button>
                            </form>

                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200">
                                            <th class="py-2 pr-4">Tanggal</th>
                                            <th class="py-2 pr-4">Keterangan</th>
                                            <th class="py-2"><span class="sr-only">Aksi</span></th>
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
                                                <td colspan="3" class="py-4 text-center text-slate-500">Belum ada hari libur.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="mt-3 space-y-1 text-sm text-slate-600">
                            @forelse ($schedule->exceptions->sortBy('exception_date') as $exception)
                                <p>{{ $exception->exception_date->translatedFormat('d F Y') }} — {{ $exception->reason ?? 'Libur' }}</p>
                            @empty
                                <p class="text-slate-500">Belum ada hari libur.</p>
                            @endforelse
                        </div>
                    @endif
                </details>
            @empty
                <p class="mt-3 text-sm text-slate-500">Belum ada pola jadwal untuk jenjang ini.</p>
            @endforelse
        </section>
    @endif
@endsection
