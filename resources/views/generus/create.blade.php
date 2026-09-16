<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Generus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="max-w-5xl mx-auto py-10 px-4">
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Tambah Generus</h1>
            <p class="text-slate-500">Form identitas dan penempatan awal</p>
        </div>

        <form method="POST" action="{{ route('generus.store') }}" class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
            @csrf

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-2" for="registration_number">Nomor Induk</label>
                    <input id="registration_number" name="registration_number" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="full_name">Nama Lengkap</label>
                    <input id="full_name" name="full_name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="gender">Jenis Kelamin</label>
                    <select id="gender" name="gender" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">-- Pilih --</option>
                        <option value="laki-laki">Laki-laki</option>
                        <option value="perempuan">Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="birth_date">Tanggal Lahir</label>
                    <input id="birth_date" name="birth_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="region_id">Daerah</label>
                    <select id="region_id" name="region_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">-- Pilih --</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="village_id">Desa</label>
                    <select id="village_id" name="village_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">-- Pilih --</option>
                        @foreach($villages as $village)
                            <option value="{{ $village->id }}">{{ $village->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="group_id">Kelompok</label>
                    <select id="group_id" name="group_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">-- Pilih --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="level_id">Jenjang</label>
                    <select id="level_id" name="level_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">-- Pilih --</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun</label>
                    <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="">-- Pilih --</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="status">Status Generus</label>
                    <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" for="assignment_status">Status Assignment</label>
                    <select id="assignment_status" name="assignment_status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="mt-5">
                <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                <a href="{{ route('generus.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>
