{{-- Ujian form fields shared by create (index) and edit. Expects: $ujian (null on create), $generus, $academicYears, $semesters. --}}
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
    <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($generus as $item)
            <option value="{{ $item->id }}" @selected(old('generus_id', $ujian?->generus_id) == $item->id)>{{ $item->full_name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun</label>
    <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($academicYears as $year)
            <option value="{{ $year->id }}" @selected(old('academic_year_id', $ujian?->academic_year_id) == $year->id)>{{ $year->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="semester_id">Semester</label>
    <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($semesters as $semester)
            <option value="{{ $semester->id }}" @selected(old('semester_id', $ujian?->semester_id) == $semester->id)>{{ $semester->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="title">Judul</label>
    <input id="title" name="title" type="text" value="{{ old('title', $ujian?->title) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="type">Tipe</label>
    <select id="type" name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="harian" @selected(old('type', $ujian?->type) === 'harian')>Ulangan Harian</option>
        <option value="mingguan" @selected(old('type', $ujian?->type) === 'mingguan')>Ulangan Mingguan</option>
        <option value="bulanan" @selected(old('type', $ujian?->type) === 'bulanan')>Ulangan Bulanan</option>
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="score">Nilai</label>
    <input id="score" name="score" type="number" step="0.01" min="0" max="100" value="{{ old('score', $ujian?->score) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="result">Hasil (huruf)</label>
    <input id="result" name="result" type="text" value="{{ old('result', $ujian?->result) }}" placeholder="Kosongkan untuk konversi otomatis dari nilai" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    <p class="mt-1 text-xs text-slate-500">Jika dikosongkan, huruf dan keterangan diambil otomatis dari <a href="{{ route('master-data.grade-scales.index') }}" class="text-amber-600 underline">master data Konversi Nilai</a> sesuai nilai di atas.</p>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="status">Status</label>
    <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="scheduled" @selected(old('status', $ujian?->status) === 'scheduled')>Terjadwal</option>
        <option value="completed" @selected(old('status', $ujian?->status) === 'completed')>Selesai</option>
        <option value="failed" @selected(old('status', $ujian?->status) === 'failed')>Gagal</option>
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
    <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes', $ujian?->notes) }}</textarea>
</div>
