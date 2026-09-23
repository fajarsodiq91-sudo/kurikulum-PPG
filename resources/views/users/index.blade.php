@extends('layouts.app')

@section('title', 'Data Pengguna')
@section('page-title', 'Data Pengguna')
@section('breadcrumb', 'Manajemen Pengguna / Data Pengguna')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Manajemen Pengguna</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Data Pengguna</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola akun, status aktif, dan peran pengguna pada sistem.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @include('layouts.partials.validation-errors')

    <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Tambah Pengguna</h2>
            <p class="mt-1 text-sm text-slate-500">Buat akun baru dan tetapkan peran.</p>

            <form class="mt-6" method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('users.partials.fields', ['user' => null])

                <button type="submit" class="mt-6 w-full rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Simpan Pengguna
                </button>
            </form>
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Daftar Pengguna</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $users->total() }} pengguna terdaftar.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-3 pr-4 font-semibold">Nama</th>
                            <th class="py-3 pr-4 font-semibold">Email</th>
                            <th class="py-3 pr-4 font-semibold">Peran</th>
                            <th class="py-3 pr-4 font-semibold">Status</th>
                            <th class="py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 pr-4">{{ $user->name }}</td>
                                <td class="py-3 pr-4">{{ $user->email }}</td>
                                <td class="py-3 pr-4">
                                    @foreach ($user->roles as $role)
                                        <span class="mr-1 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-700">{{ $role->name }}</span>
                                    @endforeach
                                    @if ($user->roles->isEmpty())
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('users.edit', $user) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Ubah</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center">
                                    <p class="font-semibold text-slate-700">Belum ada data pengguna</p>
                                    <p class="mt-1 text-sm text-slate-500">Tambahkan pengguna pertama untuk mulai mengelola akses sistem.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </section>
    </div>
@endsection
