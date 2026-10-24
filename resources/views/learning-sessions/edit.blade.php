@extends('layouts.app')

@section('title', 'Lengkapi Jadwal KBM')
@section('page-title', 'Lengkapi Jadwal KBM')
@section('breadcrumb', 'KBM / Lengkapi Jadwal')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">KBM</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Lengkapi Jadwal KBM</h2>
            <p class="mt-2 text-sm text-slate-500">Materi, tanggal, dan {{ $session->levelLabel() }} mengikuti jadwal KBM mingguan nasional sehingga tidak dapat diubah di sini.</p>
        </div>
        <a href="{{ route('learning-sessions.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke daftar</a>
    </div>

    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <dl class="mb-6 grid gap-3 rounded-lg bg-slate-50 p-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="font-medium text-slate-500">Materi</dt>
                <dd class="text-slate-900">{{ $session->material?->title ?? '-' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">Tanggal</dt>
                <dd class="text-slate-900">{{ $session->session_date }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-500">{{ $session->levelLabel() }}</dt>
            </div>
        </dl>

        <form method="POST" action="{{ route('learning-sessions.update', $session) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2" for="teacher_id">Guru</label>
                <select id="teacher_id" name="teacher_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" @selected(old('teacher_id', $session->teacher_id) == $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" for="start_time">Jam Mulai</label>
                    <input id="start_time" name="start_time" type="time" value="{{ old('start_time', $session->start_time) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2" for="end_time">Jam Selesai</label>
                    <input id="end_time" name="end_time" type="time" value="{{ old('end_time', $session->end_time) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2" for="location">Lokasi</label>
                <input id="location" name="location" type="text" value="{{ old('location', $session->location) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2" for="status">Status</label>
                <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="scheduled" @selected(old('status', $session->status) === 'scheduled')>Terjadwal</option>
                    <option value="completed" @selected(old('status', $session->status) === 'completed')>Selesai</option>
                    <option value="cancelled" @selected(old('status', $session->status) === 'cancelled')>Dibatalkan</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes', $session->notes) }}</textarea>
            </div>
            <button type="submit" class="w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan</button>
        </form>
    </section>
@endsection
