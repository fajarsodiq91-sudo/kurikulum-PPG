@extends('layouts.app')

@section('title', 'Master Data Dapukan')
@section('page-title', 'Master Data Dapukan')
@section('breadcrumb', 'Master Data / Dapukan')

@php
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100';
    $buttonClass = 'mt-4 rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800';
@endphp

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Master Data</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Dapukan</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola daftar dapukan (jabatan/posisi) pada setiap peran organisasi. Dipakai saat seseorang mendaftar mandiri maupun saat admin menetapkan peran pengguna. Khusus peran Daerah, dapukan yang ditandai "Bisa menyetujui" adalah PH PPG — satu-satunya yang berhak menyetujui pendaftaran di tingkat Daerah.</p>
    </div>

    @if (\App\Support\Access::can('manage-master-data'))
    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('master-data.export', 'dapukans'), 'importUrl' => route('master-data.import', 'dapukans')])
    @endif

    <section class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-bold text-slate-950">Tambah Dapukan</h3>
        @if (\App\Support\Access::can('manage-master-data'))
<form method="POST" action="{{ route('master-data.dapukans.store') }}" class="mt-4">
            @csrf
            <div class="grid gap-3 sm:grid-cols-2">
                <select class="{{ $inputClass }} sm:col-span-2" name="role_slug" required>
                    <option value="">-- Pilih Peran --</option>
                    @foreach ($roleSlugs as $slug => $label)
                        <option value="{{ $slug }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input class="{{ $inputClass }}" name="group_label" placeholder="Kelompok bidang (opsional), contoh: PH PPG">
                <input class="{{ $inputClass }}" name="name" placeholder="Nama dapukan, contoh: Sekretaris" required>
                <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="0" placeholder="Urutan" required>
            </div>
            <input type="hidden" name="is_active" value="1">
            <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="can_approve" value="0">
                <input type="checkbox" name="can_approve" value="1">
                Bisa menyetujui pendaftaran (khusus PH PPG di Daerah)
            </label>
            <button class="{{ $buttonClass }}">Tambah Dapukan</button>
        </form>
@endif
    </section>

    @foreach ($roleSlugs as $slug => $label)
        <section class="mt-6 max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-slate-950">{{ $label }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ ($dapukans[$slug] ?? collect())->count() }} dapukan terdaftar.</p>

            <div class="mt-5 space-y-2 text-sm">
                @forelse (($dapukans[$slug] ?? collect()) as $dapukan)
                    <details class="border-t border-slate-100 pt-2">
                        <summary class="flex cursor-pointer list-none justify-between">
                            <span>{{ $dapukan->name }} @if ($dapukan->group_label)<span class="text-slate-400">· {{ $dapukan->group_label }}</span>@endif @if ($dapukan->can_approve)<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Bisa menyetujui</span>@endif</span>
                            <span class="flex items-center gap-3"><span>{{ $dapukan->is_active ? 'Aktif' : 'Nonaktif' }}</span>@if (\App\Support\Access::can('manage-master-data'))<span class="text-amber-600" title="Edit" aria-label="Edit"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></span>@endif</span>
                        </summary>
                        @if (\App\Support\Access::can('manage-master-data'))
<form method="POST" action="{{ route('master-data.dapukans.update', $dapukan) }}" class="mt-3 rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-2">
                                <select class="{{ $inputClass }} sm:col-span-2" name="role_slug" required>
                                    @foreach ($roleSlugs as $optionSlug => $optionLabel)
                                        <option value="{{ $optionSlug }}" @selected($dapukan->role_slug === $optionSlug)>{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                                <input class="{{ $inputClass }}" name="group_label" value="{{ $dapukan->group_label }}" placeholder="Kelompok bidang (opsional)">
                                <input class="{{ $inputClass }}" name="name" value="{{ $dapukan->name }}" required>
                                <input class="{{ $inputClass }}" name="sort_order" type="number" min="0" value="{{ $dapukan->sort_order }}" required>
                            </div>
                            <label class="mt-3 flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="can_approve" value="0">
                                <input type="checkbox" name="can_approve" value="1" @checked($dapukan->can_approve)>
                                Bisa menyetujui pendaftaran (khusus PH PPG di Daerah)
                            </label>
                            <label class="mt-2 flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" @checked($dapukan->is_active)>
                                Aktif
                            </label>
                            <button class="mt-3 block rounded-lg bg-brand-950 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Simpan Perubahan</button>
                        </form>
@endif
                    </details>
                @empty
                    <p class="text-slate-400">Belum ada dapukan untuk peran ini.</p>
                @endforelse
            </div>
        </section>
    @endforeach
@endsection
