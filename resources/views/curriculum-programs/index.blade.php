@extends('layouts.app')

@section('title', 'Program Kurikulum')
@section('page-title', 'Program Kurikulum')
@section('breadcrumb', 'Kurikulum / Program')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Kurikulum</p>
        <div class="mt-1 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-950">Program Kurikulum</h2>
                <p class="mt-2 text-sm text-slate-500">Kelola program pembinaan dan struktur materi.</p>
            </div>
        </div>
    </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Program</h2>
                <form method="POST" action="{{ route('curriculum-programs.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="name">Nama Program</label>
                        <input id="name" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="code">Kode</label>
                        <input id="code" name="code" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="description">Deskripsi</label>
                        <textarea id="description" name="description" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" checked>
                            Aktif
                        </label>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        Simpan
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Program</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Nama</th>
                                <th class="py-3 pr-4">Kode</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($programs as $program)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $program->name }}</td>
                                    <td class="py-3 pr-4">{{ $program->code }}</td>
                                    <td class="py-3 pr-4">
                                        @if($program->is_active)
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada program kurikulum.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $programs->links() }}</div>
            </div>
        </div>
    </div>
@endsection
