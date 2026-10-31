@extends('layouts.app')

@section('title', 'Struktur Organisasi')
@section('page-title', 'Struktur Organisasi')
@section('breadcrumb', 'Organisasi / Struktur Organisasi')

@section('content')
    <style>
        .org-chart { display: inline-block; min-width: 100%; }
        .org-chart ul { padding-top: 20px; position: relative; display: flex; justify-content: center; }
        .org-chart li { display: flex; flex-direction: column; align-items: center; list-style-type: none; position: relative; padding: 20px 14px 0 14px; }
        .org-chart li::before, .org-chart li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid #cbd5e1; width: 50%; height: 20px; }
        .org-chart li::after { right: auto; left: 50%; border-left: 2px solid #cbd5e1; }
        .org-chart li:only-child::before, .org-chart li:only-child::after { display: none; }
        .org-chart li:only-child { padding-top: 0; }
        .org-chart li:first-child::before, .org-chart li:last-child::after { border: 0 none; }
        .org-chart li:last-child::before { border-right: 2px solid #cbd5e1; border-radius: 0 6px 0 0; }
        .org-chart li:first-child::after { border-radius: 6px 0 0 0; }
        .org-chart ul ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid #cbd5e1; width: 0; height: 20px; }
        .org-node { display: inline-block; border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #fff; padding: 0.75rem 1rem; min-width: 11rem; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
        .org-node--daerah { border-color: #f59e0b; background: #fffbeb; }
        .org-node--desa { border-color: #93c5fd; background: #eff6ff; }
        .org-node-title { font-weight: 700; font-size: 0.8rem; color: #0f172a; }
        .org-node-person { border: none; background: none; padding: 0.25rem 0.25rem; margin-top: 0.25rem; font-size: 0.7rem; color: #475569; border-radius: 0.375rem; }
        .org-node-person:hover { background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }
        .org-node-person span { display: block; color: #94a3b8; }
        .org-leaf-frame { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 2px solid #cbd5e1; width: 100%; }
        .org-leaf-frame-title { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: #64748b; text-align: center; margin-bottom: 0.5rem; }
        .org-leaf-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; }
        .org-leaf { border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc; padding: 0.5rem 0.75rem; min-width: 8rem; }
        .org-leaf-title { font-size: 0.75rem; font-weight: 600; color: #0f172a; }
        .org-leaf-person { border: none; background: none; padding: 0.125rem 0.25rem; font-size: 0.7rem; color: #475569; border-radius: 0.375rem; }
        .org-leaf-person:hover { background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }
        .org-leaf-person span { display: block; color: #94a3b8; }
        .org-leaf-empty { font-size: 0.7rem; color: #94a3b8; }
    </style>

    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Organisasi</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Struktur Organisasi</h2>
        <p class="mt-2 text-sm text-slate-500">
            @if ($isGlobal)
                Bagan kepengurusan aktif PPG Daerah, Desa, dan Kelompok.
            @else
                Bagan kepengurusan aktif sesuai wilayah penempatan Anda.
            @endif
        </p>
    </div>

    @php
        $daerahPengurus = $pengurusDaerah->get('global', collect());
    @endphp

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @if ($daerahPengurus->isEmpty() && $villages->isEmpty())
            <p class="py-10 text-center text-sm text-slate-500">Belum ada struktur kepengurusan aktif untuk ditampilkan.</p>
        @else
            <div class="overflow-x-auto">
                <div class="org-chart">
                    <ul>
                        <li>
                            <div class="org-node org-node--daerah">
                                <p class="org-node-title">PPG {{ $region?->name ?? 'Daerah' }}</p>
                                @forelse ($daerahPengurus as $pengurus)
                                    @include('organization-units.partials.person-button', ['pengurus' => $pengurus, 'class' => 'org-node-person', 'canManageTasks' => $canManageTasks])
                                @empty
                                    <p class="org-node-person">Belum ada pengurus aktif</p>
                                @endforelse
                            </div>

                            @if ($villages->isNotEmpty())
                                <ul>
                                    @foreach ($villages as $village)
                                        @php $desaPengurus = $perwakilanDesa->get($village->id, collect()); @endphp
                                        <li>
                                            <div class="org-node org-node--desa">
                                                <p class="org-node-title">Desa {{ $village->name }}</p>
                                                @forelse ($desaPengurus as $pengurus)
                                                    @include('organization-units.partials.person-button', ['pengurus' => $pengurus, 'class' => 'org-node-person', 'canManageTasks' => $canManageTasks])
                                                @empty
                                                    <p class="org-node-person">Belum ada perwakilan aktif</p>
                                                @endforelse
                                            </div>

                                            @if ($village->groups->isNotEmpty())
                                                <div class="org-leaf-frame">
                                                    <p class="org-leaf-frame-title">Pelaksana PPG Kelompok</p>
                                                    <div class="org-leaf-grid">
                                                        @foreach ($village->groups as $group)
                                                            @php $kelompokPengurus = $pelaksanaKelompok->get($group->id, collect()); @endphp
                                                            <div class="org-leaf">
                                                                <p class="org-leaf-title">Kelompok {{ $group->name }}</p>
                                                                @forelse ($kelompokPengurus as $pengurus)
                                                                    @include('organization-units.partials.person-button', ['pengurus' => $pengurus, 'class' => 'org-leaf-person', 'canManageTasks' => $canManageTasks])
                                                                @empty
                                                                    <p class="org-leaf-empty">Belum ada pengurus aktif</p>
                                                                @endforelse
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>
        @endif
    </section>

    @include('organization-units.partials.person-detail-modal')

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-950">Daftar Unit Organisasi</h3>
        <p class="mt-1 text-sm text-slate-500">Unit organisasi dengan status "Aktif" akan muncul sebagai pilihan pada form Penempatan.</p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase text-slate-500">
                        <th class="py-2">Nama</th>
                        <th class="py-2">Kode</th>
                        <th class="py-2">Jenis</th>
                        <th class="py-2">Pimpinan</th>
                        <th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizationUnits as $unit)
                        <tr class="border-b border-slate-100">
                            <td class="py-2">{{ $unit->name }}</td>
                            <td class="py-2">{{ $unit->code }}</td>
                            <td class="py-2">{{ $unit->unit_type }}</td>
                            <td class="py-2">{{ $unit->leader_name ?? '-' }}</td>
                            <td class="py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $unit->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $unit->status === 'active' ? 'Aktif' : $unit->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-sm text-slate-500">Belum ada unit organisasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canManageOrganizationUnits)
            <form action="{{ route('organization-units.store') }}" method="POST" class="mt-6 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama Unit</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700">Kode</label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="unit_type" class="block text-sm font-medium text-slate-700">Jenis Unit</label>
                    <input type="text" name="unit_type" id="unit_type" value="{{ old('unit_type', 'bidang') }}" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('unit_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="leader_name" class="block text-sm font-medium text-slate-700">Nama Pimpinan</label>
                    <input type="text" name="leader_name" id="leader_name" value="{{ old('leader_name') }}"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                    @error('leader_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
                    <select name="status" id="status" required
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">
                        <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Tidak Aktif</option>
                    </select>
                    @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-slate-700">Keterangan</label>
                    <textarea name="description" id="description" rows="2"
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-amber-500 focus:ring-amber-500">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                        Tambah Unit Organisasi
                    </button>
                </div>
            </form>
        @endif
    </section>
@endsection
