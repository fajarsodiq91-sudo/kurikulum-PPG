@extends('layouts.app')

@section('title', 'Follow Up')
@section('page-title', 'Follow Up Pembinaan')
@section('breadcrumb', 'Follow Up Pembinaan')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Follow Up</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Follow Up Pembinaan</h2>
        <p class="mt-2 text-sm text-slate-500">Catat tindak lanjut dan prioritas pembinaan generus.</p>
    </div>

    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('follow-ups.export'), 'importUrl' => \App\Support\Access::can('manage-follow-ups') ? route('follow-ups.import') : null])

    <div class="grid gap-6 lg:grid-cols-3">
        @if (\App\Support\Access::can('manage-follow-ups'))
            <x-card class="lg:col-span-1">
                <h2 class="mb-4 text-xl font-semibold">Tambah Follow Up</h2>
                <form method="POST" action="{{ route('follow-ups.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
                        <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($generus as $item)
                                <option value="{{ $item->id }}">{{ $item->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="teacher_id">Guru Pembina</label>
                        <select id="teacher_id" name="teacher_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="title">Judul</label>
                        <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="priority">Prioritas</label>
                        <select id="priority" name="priority" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="low">Rendah</option>
                            <option value="medium">Sedang</option>
                            <option value="high">Tinggi</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="follow_up_date">Tanggal Follow Up</label>
                        <input id="follow_up_date" name="follow_up_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="scheduled">Terjadwal</option>
                            <option value="in-progress">Berjalan</option>
                            <option value="done">Selesai</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="next_action">Tindakan Selanjutnya</label>
                        <textarea id="next_action" name="next_action" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </form>
            </x-card>
        @endif

        <x-card class="lg:col-span-2">
            <h2 class="mb-4 text-xl font-semibold">Daftar Follow Up</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Generus</th>
                            <th class="py-3 pr-4">Judul</th>
                            <th class="py-3 pr-4">Prioritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($followUps as $followUp)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">{{ $followUp->generus?->full_name ?? '-' }}</td>
                                <td class="py-3 pr-4">{{ $followUp->title }}</td>
                                <td class="py-3 pr-4">{{ $followUp->priority }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6"><x-empty-state title="Belum ada follow up." class="border-none bg-transparent" /></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $followUps->links() }}</div>
        </x-card>
    </div>
@endsection
