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

    @include('layouts.partials.validation-errors')

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Tambah Wali</h2>
            <p class="mt-1 text-sm text-slate-500">Tambahkan data orang tua atau wali.</p>

            <form class="mt-6" method="POST" action="{{ route('guardians.store') }}">
                    @csrf
                    @include('guardians.partials.fields', ['guardian' => null])

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
                            <th class="py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($guardians as $guardian)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 pr-4">{{ $guardian->full_name }}</td>
                                <td class="py-3 pr-4">{{ $guardian->relationship }}</td>
                                <td class="py-3 pr-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $guardian->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $guardian->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="py-3 text-right"><a href="{{ route('guardians.edit', $guardian) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center"><p class="font-semibold text-slate-700">Belum ada data orang tua / wali</p><p class="mt-1 text-sm text-slate-500">Tambahkan wali pertama untuk mulai mengelola relasi keluarga.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $guardians->links() }}</div>
        </section>
    </div>
@endsection
