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
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="registration_number">Nomor Induk Otomatis</label>
                    <input id="registration_number" type="text" readonly value="{{ $generatedRegistrationNumber }}" class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600">
                    <p class="mt-1 text-xs text-slate-500">Format YYMM0001 dan dibuat ulang oleh server saat data disimpan.</p>
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
                        <option value="pindah_sambung" @selected(old('status') === 'pindah_sambung')>Pindah Sambung</option>
                        <option value="married" @selected(old('status') === 'married')>Sudah Menikah</option>
                    </select>
                </div>
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-bold text-slate-950">Data Referensi Lama</h3>
                <p class="mt-1 text-sm text-slate-500">Kolom ini menampung data dari aplikasi generus sebelumnya.</p>
            </div>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                @foreach([
                    ['record_number', 'Nomor Data Otomatis'],
                    ['school_name', 'Madrasah'],
                    ['nis', 'NIS'],
                    ['father_name', 'Nama Ayah'],
                    ['mother_name', 'Nama Ibu'],
                    ['father_occupation', 'Pekerjaan Ayah'],
                    ['mother_occupation', 'Pekerjaan Ibu'],
                    ['phone_number', 'Nomor WhatsApp'],
                    ['birth_place', 'Tempat Lahir'],
                    ['school_grade', 'Kelas Sekolah'],
                    ['learning_class', 'Kelas KBM'],
                    ['educational_level', 'Jenjang Generus'],
                ] as [$field, $label])
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700" for="{{ $field }}">{{ $label }}</label>
                        @if (in_array($field, ['record_number', 'nis'], true))
                            <input id="{{ $field }}" type="text" readonly value="{{ $field === 'nis' ? $generatedRegistrationNumber : $generatedRecordNumber }}" class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600">
                            <p class="mt-1 text-xs text-slate-500">{{ $field === 'nis' ? 'Nomor induk mengikuti generator otomatis.' : 'Nomor urut lama dalam format 0001.' }}</p>
                        @else
                            <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        @endif
                    </div>
                @endforeach
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="birth_order">Anak Ke</label>
                    <input id="birth_order" name="birth_order" type="number" min="1" value="{{ old('birth_order') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="sibling_count">Jumlah Saudara</label>
                    <input id="sibling_count" name="sibling_count" type="number" min="0" value="{{ old('sibling_count') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                </div>
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h3 class="text-lg font-bold text-slate-950">Pindah Sambung</h3>
                <p class="mt-1 text-sm text-slate-500">Jika status Pindah Sambung, tentukan tujuan penempatan terbaru.</p>
            </div>
            <div id="transfer-section" class="mt-6 hidden rounded-xl border border-amber-200 bg-amber-50 p-4">
                <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_destination">Tujuan Pindah Sambung <span class="text-rose-500">*</span></label>
                <select id="transfer_destination" name="transfer_destination" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                    <option value="">-- Pilih tujuan --</option>
                    <option value="internal" @selected(old('transfer_destination') === 'internal')>Daerah terdaftar di sistem</option>
                    <option value="external" @selected(old('transfer_destination') === 'external')>Luar daerah</option>
                </select>
                <p class="mt-2 text-xs text-slate-600">Untuk daerah terdaftar, pilih daerah, desa, dan kelompok pada bagian di bawah.</p>
            </div>
            <div id="placement-section" class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="region_id">Daerah <span class="text-rose-500">*</span></label>
                    <select id="region_id" name="region_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="village_id">Desa <span class="text-rose-500">*</span></label>
                    <select id="village_id" name="village_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($villages as $village)
                            <option value="{{ $village->id }}" @selected(old('village_id') == $village->id)>{{ $village->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="group_id">Kelompok <span class="text-rose-500">*</span></label>
                    <select id="group_id" name="group_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="level_id">Jenjang <span class="text-rose-500">*</span></label>
                    <select id="level_id" name="level_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                        <option value="">-- Pilih --</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="academic_year_id">Tahun <span class="text-rose-500">*</span></label>
                    <select id="academic_year_id" name="academic_year_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
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

    <script>
        const statusField = document.getElementById('status');
        const transferSection = document.getElementById('transfer-section');
        const transferDestination = document.getElementById('transfer_destination');
        const placementSection = document.getElementById('placement-section');
        const placementFields = ['region_id', 'village_id', 'group_id', 'level_id', 'academic_year_id'].map((id) => document.getElementById(id));

        function updateTransferFields() {
            const isTransfer = statusField.value === 'pindah_sambung';
            const isExternal = isTransfer && transferDestination.value === 'external';

            transferSection.classList.toggle('hidden', !isTransfer);
            placementSection.classList.toggle('hidden', isExternal);
            transferDestination.required = isTransfer;
            placementFields.forEach((field) => {
                field.required = !isExternal;
                field.disabled = isExternal;
            });
        }

        statusField.addEventListener('change', updateTransferFields);
        transferDestination.addEventListener('change', updateTransferFields);
        updateTransferFields();
    </script>
@endsection
