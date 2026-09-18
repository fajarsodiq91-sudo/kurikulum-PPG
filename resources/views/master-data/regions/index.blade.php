@extends('layouts.app')

@section('title', 'Master Data Daerah')
@section('page-title', 'Master Data Daerah')
@section('breadcrumb', 'Master Data / Daerah')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Master Data</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Daerah / DPD</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola wilayah organisasi yang menjadi dasar pemetaan data PPG.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Tambah Daerah</h2>
            <p class="mt-1 text-sm text-slate-500">Daftarkan wilayah baru ke master data.</p>

            <form class="mt-6" method="POST" action="{{ route('master-data.regions.store') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Nama Daerah <span class="text-rose-500">*</span></label>
                            <input id="name" name="name" type="text" required value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="code">Kode <span class="text-rose-500">*</span></label>
                            <input id="code" name="code" type="text" required value="{{ old('code') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="description">Deskripsi</label>
                            <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <label class="mt-5 inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Aktif
                    </label>

                    <button type="submit" class="mt-6 w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Simpan Daerah
                    </button>
                </form>
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Daftar Daerah</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $regions->count() }} daerah terdaftar.</p>
                </div>
            </div>

                <div class="overflow-x-auto">
                    <table class="mt-5 min-w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="py-3 pr-4 font-semibold">Nama</th>
                                <th class="py-3 pr-4 font-semibold">Kode</th>
                                <th class="py-3 pr-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($regions as $region)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="py-3 pr-4">{{ $region->name }}</td>
                                    <td class="py-3 pr-4">{{ $region->code }}</td>
                                    <td class="py-3 pr-4">
                                        @if($region->is_active)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-10 text-center">
                                        <p class="font-semibold text-slate-700">Belum ada data daerah</p>
                                        <p class="mt-1 text-sm text-slate-500">Gunakan formulir di samping untuk menambahkan daerah pertama.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
        </section>
    </div>
@endsection
