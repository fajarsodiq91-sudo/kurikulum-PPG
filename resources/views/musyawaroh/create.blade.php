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
            <p class="mb-3 text-xs text-slate-500">Cari nama lalu tandai Hadir/Izin/Alpha, atau centang judul kelompok untuk menandai semua anggotanya Hadir sekaligus.</p>

            <input type="text" data-attendance-search placeholder="Cari nama pengurus..." class="mb-4 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">

            @forelse ($tree as $node)
                @include('musyawaroh.partials.attendance-tree', ['node' => $node, 'level' => 0, 'collapsed' => $defaultCollapsed])
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
                <p class="mt-1 text-xs text-slate-400">Nama di sini otomatis tercatat Hadir (tamu di luar kepengurusan).</p>
            </div>
        </x-card>

        <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 md:w-auto">Simpan Musyawaroh</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-attendance-group]').forEach(function (group) {
                const toggle = group.querySelector('[data-group-toggle]');
                const hadirRadios = group.querySelectorAll('[data-group-member]');
                const allRadios = group.querySelectorAll('[data-attendance-control] input[type="radio"]');

                function syncToggle() {
                    const hadirCount = Array.from(hadirRadios).filter((r) => r.checked).length;
                    const anyMarked = Array.from(allRadios).some((r) => r.checked);
                    toggle.checked = hadirRadios.length > 0 && hadirCount === hadirRadios.length;
                    toggle.indeterminate = anyMarked && !toggle.checked;
                }

                toggle.addEventListener('change', function () {
                    if (toggle.checked) {
                        hadirRadios.forEach((r) => { r.checked = true; });
                    } else {
                        allRadios.forEach((r) => { r.checked = false; });
                    }
                    syncToggle();
                });

                allRadios.forEach((r) => r.addEventListener('change', syncToggle));
                syncToggle();
            });

            document.querySelectorAll('[data-clear-attendance]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const control = button.closest('[data-attendance-control]');
                    control.querySelectorAll('input[type="radio"]').forEach(function (radio) {
                        radio.checked = false;
                        radio.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                });
            });

            document.querySelectorAll('[data-node-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const node = button.closest('[data-attendance-node]');
                    node.querySelector('[data-node-body]').classList.toggle('hidden');
                    node.querySelector('[data-node-chevron]').classList.toggle('rotate-90');
                });
            });

            const searchInput = document.querySelector('[data-attendance-search]');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const query = searchInput.value.trim().toLowerCase();

                    document.querySelectorAll('[data-member-row]').forEach(function (row) {
                        row.classList.toggle('hidden', query !== '' && !row.dataset.memberName.includes(query));
                    });

                    document.querySelectorAll('[data-attendance-node]').forEach(function (node) {
                        const body = node.querySelector('[data-node-body]');
                        const chevron = node.querySelector('[data-node-chevron]');

                        if (query === '') {
                            const collapsed = node.dataset.defaultCollapsed === '1';
                            body.classList.toggle('hidden', collapsed);
                            chevron.classList.toggle('rotate-90', !collapsed);

                            return;
                        }

                        const hasVisibleMember = Array.from(node.querySelectorAll('[data-member-row]')).some((row) => !row.classList.contains('hidden'));

                        if (hasVisibleMember) {
                            body.classList.remove('hidden');
                            chevron.classList.add('rotate-90');
                        }
                    });
                });
            }

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
