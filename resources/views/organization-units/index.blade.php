@extends('layouts.app')

@section('title', 'Struktur Organisasi')
@section('page-title', 'Struktur Organisasi')
@section('breadcrumb', 'Organisasi / Struktur Organisasi')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Organisasi</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Unit Organisasi</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola struktur organisasi dan bidang kerja.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Tambah Unit</h2>
            <p class="mt-1 text-sm text-slate-500">Tambahkan unit baru ke struktur organisasi.</p>
                <form method="POST" action="{{ route('organization-units.store') }}">
                    @csrf
                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Nama <span class="text-rose-500">*</span></label>
                            <input id="name" name="name" type="text" required value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="code">Kode <span class="text-rose-500">*</span></label>
                            <input id="code" name="code" type="text" required value="{{ old('code') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="leader_name">Penanggung Jawab</label>
                            <input id="leader_name" name="leader_name" type="text" value="{{ old('leader_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="unit_type">Tipe Unit</label>
                            <select id="unit_type" name="unit_type" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                                <option value="bidang">Bidang</option>
                                <option value="departemen">Departemen</option>
                                <option value="tim">Tim</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Status</label>
                            <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="description">Deskripsi</label>
                            <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('description') }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="mt-6 w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Simpan Unit</button>
                </form>
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Daftar Unit</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $organizationUnits->total() }} unit terdaftar.</p>
                </div>
            </div>
                <div class="overflow-x-auto">
                    <table class="mt-5 min-w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="py-3 pr-4 font-semibold">Nama</th>
                                <th class="py-3 pr-4 font-semibold">Kode</th>
                                <th class="py-3 pr-4 font-semibold">Tipe</th>
                                <th class="py-3 pr-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($organizationUnits as $unit)
                                <tr class="border-b border-slate-100 last:border-0">
                                    <td class="py-3 pr-4">{{ $unit->name }}</td>
                                    <td class="py-3 pr-4">{{ $unit->code }}</td>
                                    <td class="py-3 pr-4">{{ $unit->unit_type }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $unit->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $unit->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center">
                                        <p class="font-semibold text-slate-700">Belum ada unit organisasi</p>
                                        <p class="mt-1 text-sm text-slate-500">Gunakan formulir di samping untuk menambahkan unit pertama.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $organizationUnits->links() }}</div>
        </section>
    </div>
@endsection
