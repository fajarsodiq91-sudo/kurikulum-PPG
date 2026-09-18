@extends('layouts.app')

@section('title', 'Presensi KBM')
@section('page-title', 'Presensi KBM')
@section('breadcrumb', 'KBM / Presensi')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">KBM</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Presensi KBM</h2>
        <p class="mt-2 text-sm text-slate-500">Catat kehadiran generus pada sesi pembelajaran.</p>
    </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Presensi</h2>
                <form method="POST" action="{{ route('session-attendances.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="learning_session_id">Sesi</label>
                        <select id="learning_session_id" name="learning_session_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->session_date }} - {{ $session->teacher?->name ?? 'Guru' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
                        <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($generus as $entry)
                                <option value="{{ $entry->id }}">{{ $entry->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="present">Hadir</option>
                            <option value="late">Terlambat</option>
                            <option value="absent">Absen</option>
                            <option value="excused">Izin</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Presensi</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Sesi</th>
                                <th class="py-3 pr-4">Generus</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $attendance->learningSession?->session_date ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $attendance->generus?->full_name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $attendance->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada presensi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
