@extends('layouts.app')

@section('title', 'Detail Generus')
@section('page-title', 'Detail Generus')
@section('breadcrumb', 'Generus / Detail Generus')

@section('content')
    @php
        $statusLabel = match ($generus->status) { 'active' => 'Aktif', 'pindah_sambung' => 'Pindah Sambung', 'married' => 'Sudah Menikah', default => $generus->status };
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div class="flex items-center gap-4">
            @if ($photoDataUri = $generus->photoDataUri())
                <img src="{{ $photoDataUri }}" alt="Foto {{ $generus->full_name }}" class="h-20 w-15 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
            @endif
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
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('generus.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke data</a>
            @if (\App\Support\Access::can('manage-generus'))
                <x-action-icon :href="route('generus.id-card', $generus)" label="ID Card" color="slate" class="!h-11 !w-11 border border-slate-300 bg-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0ZM3.75 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109" /></svg></x-action-icon>
                <x-action-icon :href="route('generus.edit', $generus)" label="Edit" color="brand" class="!h-11 !w-11 bg-brand-950 text-white hover:bg-brand-800 hover:text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>
                <form method="POST" action="{{ route('generus.destroy', $generus) }}" onsubmit="return confirm(@js('Hapus generus '.$generus->full_name.'? Data dapat dipulihkan oleh administrator database.'))">
                    @csrf
                    @method('DELETE')
                    <x-action-icon label="Hapus" color="rose" type="submit" class="!h-11 !w-11 border border-rose-200 bg-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></x-action-icon>
                </form>
            @endif
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
                'Kelas Sekolah' => $generus->schoolGrade?->name,
                'Kelas KBM' => $generus->learningClass?->name,
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
                            <td class="py-3 pr-4">{{ $assignment->assigned_at ? \Illuminate\Support\Carbon::parse($assignment->assigned_at)->format('d/m/Y') : '-' }}</td>
                            <td class="py-3 pr-4">{{ $assignment->ended_at ? \Illuminate\Support\Carbon::parse($assignment->ended_at)->format('d/m/Y') : '-' }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $assignment->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-slate-500">Belum ada riwayat penempatan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
