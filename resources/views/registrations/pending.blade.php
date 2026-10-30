@extends('layouts.app')

@section('title', 'Persetujuan Pendaftaran')
@section('page-title', 'Persetujuan Pendaftaran')
@section('breadcrumb', 'Manajemen Pengguna / Persetujuan Pendaftaran')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Manajemen Pengguna</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Persetujuan Pendaftaran</h2>
        <p class="mt-2 text-sm text-slate-500">Pendaftaran mandiri yang menunggu persetujuan Anda, sesuai peran dan cakupan Anda sendiri.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-950">Menunggu Persetujuan</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $users->total() }} pendaftaran menunggu.</p>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="py-3 pr-4 font-semibold">Nama</th>
                        <th class="py-3 pr-4 font-semibold">Email</th>
                        <th class="py-3 pr-4 font-semibold">Peran &amp; Dapukan</th>
                        <th class="py-3 pr-4 font-semibold">Cakupan</th>
                        <th class="py-3 pr-4 font-semibold">Mendaftar</th>
                        <th class="py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $role = $user->roles->first();
                            $pivot = $role?->pivot;
                            $scopeLabel = match ($pivot?->scope_type) {
                                'village' => $villages[$pivot->scope_id] ?? '-',
                                'group' => $groups[$pivot->scope_id]?->village?->name.' / '.$groups[$pivot->scope_id]?->name,
                                default => 'Seluruh Daerah',
                            };
                        @endphp
                        <tr class="border-b border-slate-100 last:border-0">
                            <td class="py-3 pr-4">{{ $user->name }}</td>
                            <td class="py-3 pr-4">{{ $user->email }}</td>
                            <td class="py-3 pr-4">
                                <span class="mr-1 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-700">{{ $role?->name }}</span>
                                <span class="text-slate-500">{{ $pivot?->dapukan_id ? ($dapukans[$pivot->dapukan_id] ?? '-') : '-' }}</span>
                            </td>
                            <td class="py-3 pr-4">{{ $scopeLabel }}</td>
                            <td class="py-3 pr-4 text-slate-500">{{ $user->created_at?->format('d M Y') }}</td>
                            <td class="py-3 text-right">
                                <form method="POST" action="{{ route('registrations.approve', $user) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('registrations.reject', $user) }}" class="ml-3 inline" onsubmit="return confirm('Tolak dan hapus pendaftaran ini?');">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Tolak</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10">
                                <x-empty-state title="Tidak ada pendaftaran menunggu" description="Semua pendaftaran dalam cakupan Anda sudah diproses." class="border-none bg-transparent" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $users->links() }}</div>
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold text-slate-950">Pengajuan Peran Tambahan</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $additionalRoleUsers->total() }} pengajuan menunggu dari anggota yang sudah aktif.</p>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="py-3 pr-4 font-semibold">Nama</th>
                        <th class="py-3 pr-4 font-semibold">Email</th>
                        <th class="py-3 pr-4 font-semibold">Peran &amp; Dapukan</th>
                        <th class="py-3 pr-4 font-semibold">Cakupan</th>
                        <th class="py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($additionalRoleUsers as $user)
                        @foreach($user->roles->where('pivot.is_active', false) as $role)
                            @php
                                $pivot = $role->pivot;
                                $scopeLabel = match ($pivot->scope_type) {
                                    'village' => $villages[$pivot->scope_id] ?? '-',
                                    'group' => $groups[$pivot->scope_id]?->village?->name.' / '.$groups[$pivot->scope_id]?->name,
                                    default => 'Seluruh Daerah',
                                };
                            @endphp
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 pr-4">{{ $user->name }}</td>
                                <td class="py-3 pr-4">{{ $user->email }}</td>
                                <td class="py-3 pr-4">
                                    <span class="mr-1 inline-flex rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold text-amber-700">{{ $role->name }}</span>
                                    <span class="text-slate-500">{{ $pivot->dapukan_id ? ($dapukans[$pivot->dapukan_id] ?? '-') : '-' }}</span>
                                </td>
                                <td class="py-3 pr-4">{{ $scopeLabel }}</td>
                                <td class="py-3 text-right">
                                    <form method="POST" action="{{ route('registrations.roles.approve', [$user, $role]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Setujui</button>
                                    </form>
                                    <form method="POST" action="{{ route('registrations.roles.reject', [$user, $role]) }}" class="ml-3 inline" onsubmit="return confirm('Tolak pengajuan peran tambahan ini?');">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Tolak</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="5" class="py-10">
                                <x-empty-state title="Tidak ada pengajuan menunggu" description="Semua pengajuan peran tambahan dalam cakupan Anda sudah diproses." class="border-none bg-transparent" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $additionalRoleUsers->links() }}</div>
    </section>
@endsection
