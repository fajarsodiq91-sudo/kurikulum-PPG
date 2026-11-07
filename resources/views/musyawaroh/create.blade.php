@extends('layouts.app')

@section('title', 'Buat Musyawaroh')
@section('page-title', 'Musyawaroh')
@section('breadcrumb', 'Buat Musyawaroh')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Pengurus</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Buat Musyawaroh</h2>
        <p class="mt-2 text-sm text-slate-500">Isi daftar hadir sesuai jalur kepengurusan Anda, lalu lanjutkan mencatat notulen setelah musyawaroh dibuat.</p>
    </div>

    <form method="POST" action="{{ route('musyawaroh.store') }}" class="space-y-6">
        @csrf

        <x-card>
            <h3 class="mb-4 text-sm font-semibold text-slate-700">Informasi Musyawaroh</h3>
            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium" for="title">Nama Musyawaroh (opsional)</label>
                    <input id="title" name="title" type="text" list="bucket-suggestions" placeholder="Musyawaroh Pengurus Oktober 2026" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('title') }}">
                    <datalist id="bucket-suggestions">
                        @foreach ($bucketSuggestions as $suggestion)
                            <option value="{{ $suggestion }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="meeting_date">Hari &amp; Tanggal</label>
                    <input id="meeting_date" name="meeting_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('meeting_date', now()->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="location">Lokasi (opsional)</label>
                    <input id="location" name="location" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('location') }}">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="start_time">Jam Mulai (opsional)</label>
                    <input id="start_time" name="start_time" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('start_time') }}">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium" for="end_time">Jam Selesai (opsional)</label>
                    <input id="end_time" name="end_time" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('end_time') }}">
                </div>
            </div>
        </x-card>

        <x-card>
            <h3 class="mb-1 text-sm font-semibold text-slate-700">Daftar Hadir</h3>
            <p class="mb-4 text-xs text-slate-500">Centang nama yang hadir. Centang pada judul kelompok untuk memilih semua anggotanya.</p>

            @forelse ($tree as $node)
                @include('musyawaroh.partials.attendance-tree', ['node' => $node, 'level' => 0])
            @empty
                <p class="text-sm text-slate-400">Tidak ada data kepengurusan yang bisa ditampilkan untuk akun Anda.</p>
            @endforelse

            <div class="mt-4 border-t border-slate-200 pt-4">
                <p class="mb-2 text-sm font-semibold text-slate-700">Nama Belum Terdaftar</p>
                <div data-manual-names-rows class="space-y-2">
                    @php $oldManualNames = old('manual_names', ['']); @endphp
                    @foreach ($oldManualNames as $manualName)
                        <div class="flex items-center gap-2" data-manual-name-row>
                            <input type="text" name="manual_names[]" value="{{ $manualName }}" placeholder="Nama tamu / belum terdaftar" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <button type="button" data-remove-manual-name class="shrink-0 rounded-lg border border-slate-300 px-2.5 py-2 text-slate-400 hover:bg-slate-50" aria-label="Hapus">&times;</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" data-add-manual-name class="mt-2 text-sm font-semibold text-brand-600 hover:text-brand-700">+ Tambah nama</button>
            </div>
        </x-card>

        <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 md:w-auto">Simpan Musyawaroh</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-attendance-group]').forEach(function (group) {
                const toggle = group.querySelector('[data-group-toggle]');
                const members = group.querySelectorAll('[data-group-member]');

                function syncToggle() {
                    const checkedCount = Array.from(members).filter((m) => m.checked).length;
                    toggle.checked = checkedCount === members.length && members.length > 0;
                    toggle.indeterminate = checkedCount > 0 && checkedCount < members.length;
                }

                toggle.addEventListener('change', function () {
                    members.forEach((m) => { m.checked = toggle.checked; });
                });

                members.forEach((m) => m.addEventListener('change', syncToggle));
                syncToggle();
            });

            const rowsContainer = document.querySelector('[data-manual-names-rows]');
            const addButton = document.querySelector('[data-add-manual-name]');

            function wireRemove(row) {
                row.querySelector('[data-remove-manual-name]').addEventListener('click', function () {
                    if (rowsContainer.querySelectorAll('[data-manual-name-row]').length > 1) {
                        row.remove();
                    } else {
                        row.querySelector('input').value = '';
                    }
                });
            }

            rowsContainer.querySelectorAll('[data-manual-name-row]').forEach(wireRemove);

            addButton.addEventListener('click', function () {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2';
                row.setAttribute('data-manual-name-row', '');
                row.innerHTML = '<input type="text" name="manual_names[]" placeholder="Nama tamu / belum terdaftar" class="w-full rounded-lg border border-slate-300 px-3 py-2">'
                    +'<button type="button" data-remove-manual-name class="shrink-0 rounded-lg border border-slate-300 px-2.5 py-2 text-slate-400 hover:bg-slate-50" aria-label="Hapus">&times;</button>';
                rowsContainer.appendChild(row);
                wireRemove(row);
            });
        });
    </script>
@endsection
