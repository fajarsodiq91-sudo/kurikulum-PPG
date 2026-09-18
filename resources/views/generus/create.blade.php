@extends('layouts.app')

@section('title', 'Tambah Generus')
@section('page-title', 'Tambah Generus')
@section('breadcrumb', 'Generus / Tambah Generus')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Generus</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Tambah Generus</h2>
            <p class="mt-2 text-sm text-slate-500">Lengkapi identitas dan penempatan awal generus.</p>
        </div>
        <a href="{{ route('generus.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Kembali ke data</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">Periksa kembali isian form.</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('generus.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <h3 class="text-lg font-bold text-slate-950">Identitas</h3>
                <p class="mt-1 text-sm text-slate-500">Data dasar generus yang akan didaftarkan.</p>
            </div>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="registration_number">Nomor Induk <span class="text-rose-500">*</span></label>
                    <input id="registration_number" name="registration_number" type="text" required value="{{ old('registration_number') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="full_name">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input id="full_name" name="full_name" type="text" required value="{{ old('full_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="gender">Jenis Kelamin</label>
                    <select id="gender" name="gender" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        <option value="laki-laki" @selected(old('gender') === 'laki-laki')>Laki-laki</option>
                        <option value="perempuan" @selected(old('gender') === 'perempuan')>Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="birth_date">Tanggal Lahir</label>
                    <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Status Generus</label>
                    <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-bold text-slate-950">Penempatan</h3>
                <p class="mt-1 text-sm text-slate-500">Pilih wilayah, jenjang, dan tahun pembinaan.</p>
            </div>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="region_id">Daerah <span class="text-rose-500">*</span></label>
                    <select id="region_id" name="region_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="village_id">Desa <span class="text-rose-500">*</span></label>
                    <select id="village_id" name="village_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($villages as $village)
                            <option value="{{ $village->id }}" @selected(old('village_id') == $village->id)>{{ $village->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="group_id">Kelompok <span class="text-rose-500">*</span></label>
                    <select id="group_id" name="group_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="level_id">Jenjang <span class="text-rose-500">*</span></label>
                    <select id="level_id" name="level_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="academic_year_id">Tahun <span class="text-rose-500">*</span></label>
                    <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="assignment_status">Status Penempatan</label>
                    <select id="assignment_status" name="assignment_status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="active" @selected(old('assignment_status', 'active') === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('assignment_status') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="mt-5">
                <label class="mb-2 block text-sm font-medium text-slate-700" for="notes">Catatan</label>
                <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('notes') }}</textarea>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('generus.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">Simpan Generus</button>
            </div>
    </form>
@endsection
