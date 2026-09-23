@extends('layouts.app')

@section('title', 'Pelatihan Guru')
@section('page-title', 'Pelatihan Guru')
@section('breadcrumb', 'Pelatihan Guru / Program')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Pelatihan Guru</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Program Pelatihan</h2>
        <p class="mt-2 text-sm text-slate-500">Kelola jadwal pelatihan dan workshop untuk guru.</p>
    </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(18rem,0.8fr)_minmax(0,1.5fr)]">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Pelatihan</h2>
                <form method="POST" action="{{ route('trainings.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="title">Judul</label>
                        <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="type">Tipe</label>
                        <select id="type" name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="workshop">Workshop</option>
                            <option value="seminar">Seminar</option>
                            <option value="training">Pelatihan</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun</label>
                        <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="scheduled_at">Tanggal</label>
                        <input id="scheduled_at" name="scheduled_at" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="location">Lokasi</label>
                        <input id="location" name="location" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="scheduled">Terjadwal</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Pelatihan</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Judul</th>
                                <th class="py-3 pr-4">Tipe</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trainings as $training)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $training->title }}</td>
                                    <td class="py-3 pr-4">{{ $training->type }}</td>
                                    <td class="py-3 pr-4">{{ $training->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada pelatihan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $trainings->links() }}</div>
            </div>
        </div>
    </div>
@endsection
