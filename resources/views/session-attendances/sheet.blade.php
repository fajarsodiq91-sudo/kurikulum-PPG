@extends('layouts.app')

@section('title', 'Daftar Hadir')
@section('page-title', 'Daftar Hadir')
@section('breadcrumb', 'KBM / Daftar Hadir')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">KBM · {{ $session->levelLabel() }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Daftar Hadir Generus</h2>
            <p class="mt-2 text-sm text-slate-500">{{ $session->session_date }} · {{ $session->material?->title ?? '-' }} · {{ $session->teacher?->name ?? '-' }}</p>
        </div>
        <a href="{{ route('learning-sessions.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke sesi</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @include('layouts.partials.validation-errors')

    <section class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        data-attendance-scanner
        data-scan-url="{{ route('learning-sessions.attendance.scan', $session) }}"
        data-faces-url="{{ route('learning-sessions.attendance.faces', $session) }}"
        data-model-url="{{ asset('models/face-api') }}">
        <h3 class="text-lg font-bold text-slate-950">Absensi dengan Scan</h3>
        <p class="mt-1 text-sm text-slate-500">Scan langsung mencatat hadir. Klik tombol lagi untuk mematikan pemindai.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" data-scan-mode="qr" aria-pressed="false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Scan QR Code</button>
            <button type="button" data-scan-mode="rfid" aria-pressed="false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Scan RFID</button>
            <button type="button" data-scan-mode="face" aria-pressed="false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Scan Wajah</button>
        </div>
        <div data-scan-pane="qr" class="mt-4 hidden max-w-sm"><div id="qr-reader" class="overflow-hidden rounded-lg"></div></div>
        <div data-scan-pane="rfid" class="mt-4 hidden max-w-sm">
            <label class="mb-2 block text-sm font-medium text-slate-700" for="rfid-input">Pembaca RFID siap</label>
            <input id="rfid-input" data-rfid-input type="text" autocomplete="off" placeholder="Tempelkan kartu..." class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </div>
        <div data-scan-pane="face" class="mt-4 hidden max-w-sm">
            <video data-face-video class="w-full rounded-lg bg-slate-900" playsinline muted></video>
            <p class="mt-2 text-xs text-slate-500">Hanya generus yang sudah diunggah fotonya yang dapat dikenali ({{ $facesWithPhoto }} dari {{ $roster->count() }}).</p>
        </div>
        <div data-scan-message role="status" aria-live="polite" class="mt-4 hidden rounded-lg border p-3 text-sm"></div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('learning-sessions.attendance.update', $session) }}">
            @csrf
            @method('PUT')
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Generus</th>
                            @foreach ($statuses as $label)
                                <th class="py-3 pr-4 text-center">{{ $label }}</th>
                            @endforeach
                            <th class="py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roster as $generus)
                            @php($current = old("attendance.{$generus->id}.status", $recorded[$generus->id]->status ?? ''))
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $generus->full_name }}</td>
                                @foreach ($statuses as $value => $label)
                                    <td class="py-3 pr-4 text-center">
                                        <input type="radio" name="attendance[{{ $generus->id }}][status]" value="{{ $value }}" aria-label="{{ $label }} {{ $generus->full_name }}" @checked($current === $value)>
                                    </td>
                                @endforeach
                                <td class="py-3">
                                    <input type="text" name="attendance[{{ $generus->id }}][notes]" value="{{ old("attendance.{$generus->id}.notes", $recorded[$generus->id]->notes ?? '') }}" maxlength="1000" class="w-full min-w-40 rounded-lg border border-slate-300 px-3 py-1.5">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($statuses) + 2 }}" class="py-6 text-center text-slate-500">Tidak ada generus aktif yang dapat Anda isi untuk sesi ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($roster->isNotEmpty())
                <p class="mt-4 text-xs text-slate-500">Generus yang belum dipilih statusnya tidak dicatat.</p>
                <button type="submit" class="mt-4 w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 sm:w-auto">Simpan Daftar Hadir</button>
            @endif
        </form>
    </section>
    @vite('resources/js/attendance-scanner.js')
@endsection
