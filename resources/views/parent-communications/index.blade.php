@extends('layouts.app')

@section('title', 'Komunikasi Orang Tua')
@section('page-title', 'Komunikasi Orang Tua')
@section('breadcrumb', 'Orang Tua / Komunikasi')

@section('content')
    @php
        $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100';
    @endphp

    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Orang Tua / Wali</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Komunikasi dengan Orang Tua</h2>
        <p class="mt-2 text-sm text-slate-500">Catat komunikasi guru dengan orang tua atau wali per murid.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @include('layouts.partials.validation-errors')

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        @if (\App\Support\Access::can('manage-parent-communications'))
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Catat Komunikasi</h2>

            <form method="POST" action="{{ route('parent-communications.store') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="generus_id">Generus <span class="text-rose-500">*</span></label>
                    <select id="generus_id" name="generus_id" required class="{{ $inputClass }}">
                        <option value="">-- Pilih --</option>
                        @foreach ($generus as $student)
                            <option value="{{ $student->id }}" @selected(old('generus_id') == $student->id)>{{ $student->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="guardian_id">Orang Tua / Wali</label>
                    <select id="guardian_id" name="guardian_id" class="{{ $inputClass }}">
                        <option value="">-- Tidak ditentukan --</option>
                        @foreach ($guardians as $guardian)
                            <option value="{{ $guardian->id }}" data-generus-ids="{{ $guardian->generus->pluck('id')->implode(',') }}" @selected(old('guardian_id') == $guardian->id)>{{ $guardian->full_name }} ({{ $guardian->relationship }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="channel">Saluran <span class="text-rose-500">*</span></label>
                    <select id="channel" name="channel" required class="{{ $inputClass }}">
                        @foreach ($channels as $channel)
                            <option value="{{ $channel }}" @selected(old('channel') === $channel)>{{ $channel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="communicated_at">Tanggal <span class="text-rose-500">*</span></label>
                    <input id="communicated_at" name="communicated_at" type="date" required value="{{ old('communicated_at', now()->toDateString()) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="subject">Perihal</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="message">Isi Komunikasi <span class="text-rose-500">*</span></label>
                    <textarea id="message" name="message" rows="4" required class="{{ $inputClass }}">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Simpan</button>
            </form>
        </section>
        @endif

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Riwayat Komunikasi</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $communications->total() }} catatan.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-3 pr-4 font-semibold">Tanggal</th>
                            <th class="py-3 pr-4 font-semibold">Generus</th>
                            <th class="py-3 pr-4 font-semibold">Orang Tua / Wali</th>
                            <th class="py-3 pr-4 font-semibold">Saluran</th>
                            <th class="py-3 pr-4 font-semibold">Isi</th>
                            <th class="py-3 font-semibold">Dicatat oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($communications as $communication)
                            <tr class="border-b border-slate-100 align-top last:border-0">
                                <td class="py-3 pr-4 whitespace-nowrap">{{ $communication->communicated_at->format('d/m/Y') }}</td>
                                <td class="py-3 pr-4">{{ $communication->generus?->full_name }}</td>
                                <td class="py-3 pr-4">{{ $communication->guardian?->full_name ?? '-' }}</td>
                                <td class="py-3 pr-4">{{ $communication->channel }}</td>
                                <td class="py-3 pr-4"><span class="font-semibold text-slate-700">{{ $communication->subject }}</span><p class="whitespace-pre-line text-slate-600">{{ $communication->message }}</p></td>
                                <td class="py-3">{{ $communication->teacher?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-sm text-slate-500">Belum ada catatan komunikasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $communications->links() }}</div>
        </section>
    </div>

    <script>
        (function () {
            const student = document.getElementById('generus_id');
            const guardian = document.getElementById('guardian_id');

            function filterGuardians() {
                Array.from(guardian.options).forEach((option) => {
                    if (option.value === '') {
                        return;
                    }

                    const linked = (option.dataset.generusIds || '').split(',');
                    option.hidden = student.value !== '' && !linked.includes(student.value);

                    if (option.hidden && option.selected) {
                        guardian.value = '';
                    }
                });
            }

            student.addEventListener('change', filterGuardians);
            filterGuardians();
        })();
    </script>
@endsection
