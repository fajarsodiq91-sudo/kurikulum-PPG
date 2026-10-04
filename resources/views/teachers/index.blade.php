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

    @include('layouts.partials.validation-errors')

    @if (\App\Support\Access::can('manage-teachers'))
        @include('layouts.partials.spreadsheet-tools', ['exportUrl' => route('teachers.export'), 'importUrl' => route('teachers.import')])
    @endif

    <div class="grid gap-6 {{ \App\Support\Access::can('manage-teachers') ? 'lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]' : '' }}">
        @if (\App\Support\Access::can('manage-teachers'))
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Tambah Guru</h2>
                <p class="mt-1 text-sm text-slate-500">Tambahkan data tenaga pendidik.</p>

                <form class="mt-6" method="POST" action="{{ route('teachers.store') }}" enctype="multipart/form-data">
                        @csrf
                        @include('teachers.partials.fields', ['teacher' => null])

                        <button type="submit" class="mt-6 w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                            Simpan Guru
                        </button>
                    </form>
            </section>
        @endif

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Daftar Guru</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $teachers->total() }} guru terdaftar.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="py-3 pr-4 font-semibold">No. Induk</th>
                            <th class="py-3 pr-4 font-semibold">Nama</th>
                            <th class="py-3 pr-4 font-semibold">Desa</th>
                            <th class="py-3 pr-4 font-semibold">Kelompok</th>
                            <th class="py-3 pr-4 font-semibold">Status</th>
                            <th class="py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr class="border-b border-slate-100 last:border-0">
                                <td class="py-3 pr-4 font-mono text-xs text-slate-600">{{ $teacher->registration_number ?? '-' }}</td>
                                <td class="py-3 pr-4">{{ $teacher->name }}</td>
                                <td class="py-3 pr-4">{{ $teacher->village?->name ?? '-' }}</td>
                                <td class="py-3 pr-4">{{ $teacher->group?->name ?? '-' }}</td>
                                <td class="py-3 pr-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $teacher->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $teacher->status === 'active' ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="py-3 text-right whitespace-nowrap">
                                    @if (\App\Support\Access::can('manage-teachers'))
                                        <x-action-icon :href="route('teachers.id-card', $teacher)" label="ID Card" color="slate"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0ZM3.75 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109" /></svg></x-action-icon><x-action-icon :href="route('teachers.edit', $teacher)" label="Edit" color="amber"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></x-action-icon>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center"><p class="font-semibold text-slate-700">Belum ada data guru</p><p class="mt-1 text-sm text-slate-500">Tambahkan guru pertama untuk mulai mengelola tenaga pendidik.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $teachers->links() }}</div>
        </section>
    </div>
@endsection
