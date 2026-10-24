@extends('layouts.app')

@section('title', 'Penugasan')
@section('page-title', 'Penugasan & Tanggung Jawab')
@section('breadcrumb', 'Organisasi / Penempatan')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Organisasi</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Penugasan & Tanggung Jawab</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola tugas guru dan unit organisasi.</p>
    </div>

    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('assignments.export'), 'importUrl' => \App\Support\Access::can('manage-assignments') ? route('assignments.import') : null])

    <div class="grid gap-6 lg:grid-cols-3">
        @if (\App\Support\Access::can('manage-assignments'))
            <x-card class="lg:col-span-1">
                <h2 class="mb-4 text-xl font-semibold">Tambah Penugasan</h2>
                <form method="POST" action="{{ route('assignments.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="organization_unit_id">Unit Organisasi</label>
                        <select id="organization_unit_id" name="organization_unit_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($organizationUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="teacher_id">Guru</label>
                        <select id="teacher_id" name="teacher_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="assignment_title">Judul Penugasan</label>
                        <input id="assignment_title" name="assignment_title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="assignment_type">Tipe</label>
                        <select id="assignment_type" name="assignment_type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="kurikulum">Kurikulum</option>
                            <option value="pembinaan">Pembinaan</option>
                            <option value="administrasi">Administrasi</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="start_date">Tanggal Mulai</label>
                        <input id="start_date" name="start_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="end_date">Tanggal Selesai</label>
                        <input id="end_date" name="end_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="active">Aktif</option>
                            <option value="completed">Selesai</option>
                            <option value="paused">Dijeda</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="responsibility">Tanggung Jawab</label>
                        <textarea id="responsibility" name="responsibility" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </form>
            </x-card>
        @endif

        <x-card class="lg:col-span-2">
            <h2 class="mb-4 text-xl font-semibold">Daftar Penugasan</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 pr-4">Judul</th>
                            <th class="py-3 pr-4">Unit</th>
                            <th class="py-3 pr-4">Guru</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $assignment)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4">{{ $assignment->assignment_title }}</td>
                                <td class="py-3 pr-4">{{ $assignment->organizationUnit?->name ?? '-' }}</td>
                                <td class="py-3 pr-4">{{ $assignment->teacher?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6"><x-empty-state title="Belum ada penugasan." class="border-none bg-transparent" /></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $assignments->links() }}</div>
        </x-card>
    </div>
@endsection
