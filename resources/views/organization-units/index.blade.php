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
        .org-node-person { font-size: 0.7rem; color: #475569; margin-top: 0.25rem; }
        .org-node-person span { display: block; color: #94a3b8; }
        .org-leaf-frame { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 2px solid #cbd5e1; width: 100%; }
        .org-leaf-frame-title { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; color: #64748b; text-align: center; margin-bottom: 0.5rem; }
        .org-leaf-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; }
        .org-leaf { border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc; padding: 0.5rem 0.75rem; min-width: 8rem; }
        .org-leaf-title { font-size: 0.75rem; font-weight: 600; color: #0f172a; }
        .org-leaf-person { font-size: 0.7rem; color: #475569; }
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
                                    <p class="org-node-person">{{ $pengurus->user_name }}<span>{{ $pengurus->jabatan ?? 'Tanpa jabatan' }}</span></p>
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
                                                    <p class="org-node-person">{{ $pengurus->user_name }}<span>{{ $pengurus->jabatan ?? 'Tanpa jabatan' }}</span></p>
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
                                                                    <p class="org-leaf-person">{{ $pengurus->user_name }} &middot; {{ $pengurus->jabatan ?? 'Tanpa jabatan' }}</p>
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
@endsection
