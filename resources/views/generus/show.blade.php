@extends('layouts.app')

@section('title', 'Detail Generus')
@section('page-title', 'Detail Generus')
@section('breadcrumb', 'Generus / Detail Generus')

@section('content')
    @php
        $statusLabel = match ($generus->status) { 'active' => 'Aktif', 'pindah_sambung' => 'Pindah Sambung', 'married' => 'Sudah Menikah', default => $generus->status };
        $assignmentStatusLabels = ['active' => 'Aktif', 'inactive' => 'Nonaktif', 'ended' => 'Selesai'];
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Generus · {{ $generus->registration_number }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $generus->full_name }}</h2>
            <p class="mt-2">
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $generus->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($generus->status === 'pindah_sambung' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ $statusLabel }}</span>
                @if ($generus->transfer_destination)
                    <span class="ml-1 text-xs text-slate-500">Tujuan: {{ $generus->transfer_destination === 'external' ? 'Luar daerah' : 'Daerah terdaftar' }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('generus.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke data</a>
            <a href="{{ route('generus.id-card', $generus) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">ID Card</a>
            <a href="{{ route('generus.edit', $generus) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Edit</a>
            <form method="POST" action="{{ route('generus.destroy', $generus) }}" onsubmit="return confirm(@js('Hapus generus '.$generus->full_name.'? Data dapat dipulihkan oleh administrator database.'))">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50">Hapus</button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Identitas</h3>
        <dl class="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                'Nomor Induk' => $generus->registration_number,
                'Nomor Data' => $generus->record_number,
                'NIS' => $generus->nis,
                'Jenis Kelamin' => $generus->gender ? ucfirst($generus->gender) : null,
                'Tempat Lahir' => $generus->birth_place,
                'Tanggal Lahir' => $generus->birth_date?->translatedFormat('d F Y'),
                'Anak Ke' => $generus->birth_order,
                'Jumlah Saudara' => $generus->sibling_count,
                'Nama Ayah' => $generus->father_name,
                'Pekerjaan Ayah' => $generus->father_occupation,
                'Nama Ibu' => $generus->mother_name,
                'Pekerjaan Ibu' => $generus->mother_occupation,
                'Nomor WhatsApp' => $generus->phone_number,
                'Madrasah' => $generus->school_name,
                'Kelas Sekolah' => $generus->school_grade,
                'Kelas KBM' => $generus->learning_class,
                'Jenjang Generus' => $generus->educational_level,
            ] as $label => $value)
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                    <dd class="mt-1 text-sm text-slate-800">{{ filled($value) ? $value : '-' }}</dd>
                </div>
            @endforeach
        </dl>
        @if (filled($generus->notes))
            <div class="mt-5 border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan</p>
                <p class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $generus->notes }}</p>
            </div>
        @endif
    </section>

    <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Riwayat Penempatan</h3>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="py-3 pr-4 font-semibold">Daerah / Desa / Kelompok</th>
                        <th class="py-3 pr-4 font-semibold">Jenjang</th>
                        <th class="py-3 pr-4 font-semibold">Tahun</th>
                        <th class="py-3 pr-4 font-semibold">Status</th>
                        <th class="py-3 pr-4 font-semibold">Mulai</th>
                        <th class="py-3 pr-4 font-semibold">Selesai</th>
                        <th class="py-3 pr-4 font-semibold">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($generus->assignments as $assignment)
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-3 pr-4">{{ $assignment->region?->name ?? '-' }} / {{ $assignment->village?->name ?? '-' }} / {{ $assignment->group?->name ?? '-' }}</td>
                            <td class="py-3 pr-4">{{ $assignment->level?->name ?? '-' }}</td>
                            <td class="py-3 pr-4">{{ $assignment->academicYear?->name ?? '-' }}</td>
                            <td class="py-3 pr-4">{{ $assignmentStatusLabels[$assignment->status] ?? $assignment->status }}</td>
                            <td class="py-3 pr-4">{{ $assignment->assigned_at ? \Illuminate\Support\Carbon::parse($assignment->assigned_at)->format('d/m/Y') : '-' }}</td>
                            <td class="py-3 pr-4">{{ $assignment->ended_at ? \Illuminate\Support\Carbon::parse($assignment->ended_at)->format('d/m/Y') : '-' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $assignment->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-sm text-slate-500">Belum ada riwayat penempatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
