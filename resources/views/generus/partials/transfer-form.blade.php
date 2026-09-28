{{--
    Intake form for an internal pindah sambung: the generus already exists in the system
    under another kelompok. Selecting them (see create.blade.php) fills these fields from
    their existing record; everything stays editable except their identity (registration
    number and NIS), which the server keeps untouched.
    Expects: $action, $villages, $groups, $levels, $classGrades.
--}}
<form id="transfer-form" method="POST" action="{{ $action }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    @csrf
    <input type="hidden" name="generus_id" id="transfer_generus_id" value="{{ old('generus_id') }}">

    <div>
        <h3 class="text-lg font-bold text-slate-950">Identitas</h3>
        <p class="mt-1 text-sm text-slate-500">Data diambil dari catatan pindah sambung generus ini dan tetap bisa diedit, kecuali Nomor Induk dan NIS.</p>
    </div>
    <div class="mt-6 grid gap-5 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_registration_number">Nomor Induk</label>
            <input id="transfer_registration_number" type="text" readonly value="-- pilih generus terlebih dahulu --" class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600">
            <p class="mt-1 text-xs text-slate-500">Nomor Induk dan NIS tetap mengikuti data asal generus ini.</p>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_nis">NIS</label>
            <input id="transfer_nis" type="text" readonly value="-- pilih generus terlebih dahulu --" class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600">
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_full_name">Nama Lengkap <span class="text-rose-500">*</span></label>
            <input id="transfer_full_name" name="full_name" type="text" required value="{{ old('full_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_gender">Jenis Kelamin</label>
            <select id="transfer_gender" name="gender" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                <option value="">-- Pilih --</option>
                <option value="laki-laki" @selected(old('gender') === 'laki-laki')>Laki-laki</option>
                <option value="perempuan" @selected(old('gender') === 'perempuan')>Perempuan</option>
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_birth_date">Tanggal Lahir</label>
            <input id="transfer_birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
        </div>
    </div>

    <div class="mt-8 border-t border-slate-200 pt-6">
        <h3 class="text-lg font-bold text-slate-950">Data Referensi Lama</h3>
        <p class="mt-1 text-sm text-slate-500">Kolom ini menampung data dari kelompok asal generus.</p>
    </div>
    <div class="mt-6 grid gap-5 md:grid-cols-2">
        @foreach([
            ['school_name', 'Madrasah'],
            ['father_name', 'Nama Ayah'],
            ['mother_name', 'Nama Ibu'],
            ['father_occupation', 'Pekerjaan Ayah'],
            ['mother_occupation', 'Pekerjaan Ibu'],
            ['phone_number', 'Nomor WhatsApp'],
            ['birth_place', 'Tempat Lahir'],
        ] as [$field, $label])
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_{{ $field }}">{{ $label }}</label>
                <input id="transfer_{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
            </div>
        @endforeach

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_school_grade_id">Kelas Sekolah</label>
            <select id="transfer_school_grade_id" name="school_grade_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                <option value="">-- Pilih --</option>
                @foreach($classGrades as $classGrade)
                    <option value="{{ $classGrade->id }}" @selected(old('school_grade_id') == $classGrade->id)>{{ $classGrade->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_learning_class_id">Kelas KBM</label>
            <select id="transfer_learning_class_id" name="learning_class_id" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                <option value="">-- Pilih --</option>
                @foreach($classGrades as $classGrade)
                    <option value="{{ $classGrade->id }}" @selected(old('learning_class_id') == $classGrade->id)>{{ $classGrade->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_birth_order">Anak Ke</label>
            <input id="transfer_birth_order" name="birth_order" type="number" min="1" value="{{ old('birth_order') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
        </div>
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_sibling_count">Jumlah Saudara</label>
            <input id="transfer_sibling_count" name="sibling_count" type="number" min="0" value="{{ old('sibling_count') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
        </div>
    </div>

    <div class="mt-8 border-t border-slate-200 pt-6">
        <h3 class="text-lg font-bold text-slate-950">Penempatan Baru</h3>
        <p class="mt-1 text-sm text-slate-500">Tentukan penempatan generus ini di kelompok tujuan.</p>
    </div>
    <div class="mt-6 grid gap-5 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_village_id">Desa <span class="text-rose-500">*</span></label>
            <select id="transfer_village_id" name="village_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                <option value="">-- Pilih --</option>
                @foreach($villages as $village)
                    <option value="{{ $village->id }}" @selected(old('village_id') == $village->id)>{{ $village->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_group_id">Kelompok <span class="text-rose-500">*</span></label>
            <select id="transfer_group_id" name="group_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                <option value="">-- Pilih desa terlebih dahulu --</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" data-village-id="{{ $group->village_id }}" @selected(old('group_id') == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_level_id">Jenjang <span class="text-rose-500">*</span></label>
            <select id="transfer_level_id" name="level_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
                <option value="">-- Pilih --</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}" @selected(old('level_id') == $level->id)>{{ $level->name }}</option>
                @endforeach
            </select>
        </div>

    </div>

    <div class="mt-5">
        <label class="mb-2 block text-sm font-medium text-slate-700" for="transfer_notes">Catatan</label>
        <textarea id="transfer_notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('notes') }}</textarea>
    </div>

    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('generus.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Batal</a>
        <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Terima Pindah Sambung</button>
    </div>
</form>
