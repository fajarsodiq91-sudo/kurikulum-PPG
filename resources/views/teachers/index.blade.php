@extends('layouts.app')

@section('title', 'Data Guru')
@section('page-title', 'Data Guru')
@section('breadcrumb', 'Guru / Data Guru')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Guru</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Data Guru</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola tenaga pendidik dan status keaktifan mereka.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Tambah Guru</h2>
            <p class="mt-1 text-sm text-slate-500">Tambahkan data tenaga pendidik.</p>

            <form class="mt-6" method="POST" action="{{ route('teachers.store') }}">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Nama <span class="text-rose-500">*</span></label>
                            <input id="name" name="name" type="text" required value="{{ old('name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="gender">Jenis Kelamin</label>
                            <select id="gender" name="gender" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                            <option value="">-- Pilih --</option>
                            <option value="laki-laki">Laki-laki</option>
                            <option value="perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="phone">Telepon</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="email">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
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
                        Simpan Guru
                    </button>
                </form>
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Daftar Guru</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $teachers->total() }} guru terdaftar.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-3 pr-4 font-semibold">Nama</th>
                            <th class="py-3 pr-4 font-semibold">Telepon</th>
                            <th class="py-3 pr-4 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 pr-4">{{ $teacher->name }}</td>
                                <td class="py-3 pr-4">{{ $teacher->phone ?? '-' }}</td>
                                <td class="py-3 pr-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $teacher->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $teacher->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-10 text-center"><p class="font-semibold text-slate-700">Belum ada data guru</p><p class="mt-1 text-sm text-slate-500">Tambahkan guru pertama untuk mulai mengelola tenaga pendidik.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $teachers->links() }}</div>
        </section>
    </div>
@endsection
