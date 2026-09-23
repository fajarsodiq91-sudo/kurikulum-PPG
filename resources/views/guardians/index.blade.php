@extends('layouts.app')

@section('title', 'Data Orang Tua / Wali')
@section('page-title', 'Data Orang Tua / Wali')
@section('breadcrumb', 'Orang Tua / Wali / Data')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Orang Tua / Wali</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Data Orang Tua / Wali</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola keterkaitan keluarga generus.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Tambah Wali</h2>
            <p class="mt-1 text-sm text-slate-500">Tambahkan data orang tua atau wali.</p>

            <form class="mt-6" method="POST" action="{{ route('guardians.store') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="full_name">Nama Lengkap <span class="text-rose-500">*</span></label>
                            <input id="full_name" name="full_name" type="text" required value="{{ old('full_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="relationship">Hubungan <span class="text-rose-500">*</span></label>
                            <select id="relationship" name="relationship" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                            <option value="">-- Pilih --</option>
                            <option value="ayah">Ayah</option>
                            <option value="ibu">Ibu</option>
                            <option value="wali">Wali</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="phone">Telepon</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="address">Alamat</label>
                            <textarea id="address" name="address" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('address') }}</textarea>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Status</label>
                            <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="notes">Catatan</label>
                            <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <button type="submit" class="mt-6 w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Simpan Wali
                    </button>
                </form>
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Daftar Orang Tua / Wali</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $guardians->total() }} wali terdaftar.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-3 pr-4 font-semibold">Nama</th>
                            <th class="py-3 pr-4 font-semibold">Hubungan</th>
                            <th class="py-3 pr-4 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guardians as $guardian)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 pr-4">{{ $guardian->full_name }}</td>
                                <td class="py-3 pr-4">{{ $guardian->relationship }}</td>
                                <td class="py-3 pr-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $guardian->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $guardian->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 text-center"><p class="font-semibold text-slate-700">Belum ada data orang tua / wali</p><p class="mt-1 text-sm text-slate-500">Tambahkan wali pertama untuk mulai mengelola relasi keluarga.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $guardians->links() }}</div>
        </section>
    </div>
@endsection
