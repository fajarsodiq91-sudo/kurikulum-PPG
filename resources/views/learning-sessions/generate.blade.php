@extends('layouts.app')

@section('title', 'Generate Jadwal Mingguan KBM')
@section('page-title', 'Generate Jadwal Mingguan KBM')
@section('breadcrumb', 'KBM / Generate Jadwal Mingguan')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">KBM</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Generate Jadwal Mingguan KBM</h2>
            <p class="mt-2 text-sm text-slate-500">Materi kurikulum yang sudah dipecah per minggu dijadwalkan sekali, lalu otomatis dibuatkan ke setiap kelompok/desa pada tanggal yang sama. Guru dan jam pelaksanaan dilengkapi masing-masing kelompok/desa.</p>
        </div>
        <a href="{{ route('learning-sessions.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke daftar</a>
    </div>

    <section class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('learning-sessions.generate.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2" for="material_id">Materi (Minggu ke-)</label>
                <select id="material_id" name="material_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="">-- Pilih --</option>
                    @foreach($materials as $material)
                        @php($category = $material->materialChapter?->materialCategory)
                        <option value="{{ $material->id }}" @selected(old('material_id') == $material->id)>
                            {{ $material->week_number ? 'Minggu '.$material->week_number.' - ' : '' }}{{ $category?->semester?->name }} - {{ $material->title }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Hanya materi aktif yang muncul. Tambahkan nomor minggu di <a href="{{ route('learning-materials.index') }}" class="text-amber-600 underline">Materi Pembelajaran</a> agar urutan mudah dipilih.</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2" for="session_date">Tanggal Pelaksanaan</label>
                <input id="session_date" name="session_date" type="date" required value="{{ old('session_date') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2" for="level">Tingkat Pelaksanaan</label>
                <select id="level" name="level" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="group" @selected(old('level', 'group') === 'group')>Kelompok ({{ $groupCount }} kelompok aktif)</option>
                    <option value="village" @selected(old('level') === 'village')>Desa ({{ $villageCount }} desa aktif)</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2" for="status">Status</label>
                <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <option value="scheduled" @selected(old('status', 'scheduled') === 'scheduled')>Terjadwal</option>
                    <option value="completed" @selected(old('status') === 'completed')>Selesai</option>
                    <option value="cancelled" @selected(old('status') === 'cancelled')>Dibatalkan</option>
                </select>
            </div>
            <p class="mb-4 text-xs text-slate-500">Jika jadwal untuk materi dan tingkat ini sudah pernah dibuat, generate ulang hanya memperbarui tanggal dan status; guru serta jam yang sudah dilengkapi tidak ikut terhapus.</p>
            <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Generate Jadwal</button>
        </form>
    </section>
@endsection
