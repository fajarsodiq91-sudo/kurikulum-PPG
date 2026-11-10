@extends('layouts.app')

@section('title', 'Akselerasi Munaqosah')
@section('page-title', 'Akselerasi Munaqosah')
@section('breadcrumb', 'Munaqosah / Akselerasi')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Munaqosah</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Akselerasi Munaqosah</h2>
        <p class="mt-2 text-sm text-slate-500">Pengajuan munaqosah akselerasi untuk generus yang naik kelas KBM di luar jadwal semester reguler. Diajukan Pelaksana PPG Kelompok, disetujui Perwakilan PPG Desa atau PPG Daerah.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if ($canPropose)
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Ajukan Akselerasi</h3>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('munaqosah-acceleration-requests.store') }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="generus_id">Generus</label>
                    <select id="generus_id" name="generus_id" required class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">-- Pilih --</option>
                        @foreach ($candidates as $candidate)
                            <option value="{{ $candidate->id }}" @selected(old('generus_id') == $candidate->id)>{{ $candidate->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="reason">Alasan</label>
                    <textarea id="reason" name="reason" rows="2" class="w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('reason') }}</textarea>
                </div>
                <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Ajukan</button>
            </form>
        </section>

        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-950">Pengajuan Saya</h3>

            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($mine as $item)
                    <div class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium text-slate-900">{{ $item->generus->full_name }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                                {{ $item->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($item->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$item->status] ?? $item->status }}
                            </span>
                        </div>
                        @if ($item->reason)
                            <p class="mt-1 text-sm text-slate-500">{{ $item->reason }}</p>
                        @endif

                        @if ($item->status === 'approved')
                            <details class="mt-3 rounded-lg bg-slate-50 p-3">
                                <summary class="cursor-pointer text-sm font-semibold text-brand-700">Isi hasil munaqosah akselerasi</summary>
                                <form method="POST" action="{{ route('munaqosah-acceleration-requests.result', $item) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                                    @csrf
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Judul</label>
                                        <input type="text" name="title" required value="Munaqosah Akselerasi" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Tahun Ajaran</label>
                                        <select name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                            @foreach (\App\Models\AcademicYear::where('is_active', true)->get() as $year)
                                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Semester</label>
                                        <select name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                            @foreach (\App\Models\Semester::where('is_active', true)->get() as $semester)
                                                <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Nilai</label>
                                        <input type="number" step="0.01" min="0" max="100" name="score" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-slate-600">Keputusan</label>
                                        <select name="decision" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                            <option value="naik">Naik Kelas</option>
                                            <option value="tinggal">Tinggal Kelas</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">Simpan Hasil</button>
                                    </div>
                                </form>
                            </details>
                        @endif
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-slate-500">Belum ada pengajuan.</p>
                @endforelse
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">Menunggu Persetujuan Saya</h3>

        <div class="mt-4 divide-y divide-slate-100">
            @forelse ($pending as $item)
                <div class="py-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium text-slate-900">{{ $item->generus->full_name }}</span>
                        <span class="text-xs text-slate-500">Diajukan oleh {{ $item->requestedBy->name }}</span>
                    </div>
                    @if ($item->reason)
                        <p class="mt-1 text-sm text-slate-500">{{ $item->reason }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('munaqosah-acceleration-requests.approve', $item) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('munaqosah-acceleration-requests.reject', $item) }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Tolak</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">Tidak ada pengajuan yang menunggu persetujuan Anda.</p>
            @endforelse
        </div>
    </section>
@endsection
