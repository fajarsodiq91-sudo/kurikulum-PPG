@extends('layouts.app')

@section('title', 'Data Pengguna')
@section('page-title', 'Data Pengguna')
@section('breadcrumb', 'Manajemen Pengguna / Data Pengguna')

@section('content')
    @php
        $formShouldOpen = $errors->any();
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Manajemen Pengguna</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Data Pengguna</h2>
            <p class="mt-2 text-sm text-slate-500">Kelola akun, status aktif, dan peran pengguna pada sistem.</p>
        </div>
        <button type="button" id="open-create-user" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">
            + Tambah Pengguna
        </button>
    </div>

    @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('users.export'), 'importUrl' => route('users.import')])

    <x-filter-bar :action="route('users.index')" search-placeholder="Cari nama atau email...">
        <x-filter-select name="status" label="Status" :options="['active' => 'Aktif', 'pending' => 'Menunggu Persetujuan', 'inactive' => 'Nonaktif']" />
        <x-filter-select name="role" label="Peran" :options="$roles" option-value="slug" option-label="name" />
    </x-filter-bar>

    <div id="create-user-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $formShouldOpen ? '' : 'hidden' }}" data-create-user-close></div>

    <div id="create-user-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $formShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-user-title">
        <div class="mx-auto my-6 w-full max-w-xl">
            <div class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-user-close>&times;</button>
                <h2 id="create-user-title" class="text-lg font-bold text-slate-950">Tambah Pengguna</h2>
                <p class="mt-1 text-sm text-slate-500">Buat akun baru dan tetapkan peran.</p>

                <form class="mt-6" method="POST" action="{{ route('users.store') }}">
                    @csrf
                    @include('users.partials.fields', ['user' => null])

                    <button type="submit" class="mt-6 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                        Simpan Pengguna
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid gap-6">
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
                                    @php
                                        $statusLabels = ['active' => 'Aktif', 'pending' => 'Menunggu Persetujuan', 'inactive' => 'Nonaktif'];
                                        $statusColors = ['active' => 'emerald', 'pending' => 'amber', 'inactive' => 'slate'];
                                    @endphp
                                    <x-badge :color="$statusColors[$user->status] ?? 'slate'">{{ $statusLabels[$user->status] ?? 'Nonaktif' }}</x-badge>
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('users.edit', $user) }}" class="text-xs font-semibold text-amber-600 hover:text-amber-700">Ubah</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10">
                                    <x-empty-state title="Belum ada data pengguna" description="Tambahkan pengguna pertama untuk mulai mengelola akses sistem." class="border-none bg-transparent" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </section>
    </div>

    <script>
        (function () {
            const openButton = document.getElementById('open-create-user');
            const backdrop = document.getElementById('create-user-backdrop');
            const modal = document.getElementById('create-user-modal');

            if (!openButton || !backdrop || !modal) {
                return;
            }

            function openModal() {
                backdrop.classList.remove('hidden');
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                backdrop.classList.add('hidden');
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            openButton.addEventListener('click', openModal);
            document.querySelectorAll('[data-create-user-close]').forEach(function (el) {
                el.addEventListener('click', closeModal);
            });
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
