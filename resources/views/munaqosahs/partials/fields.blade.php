{{-- Munaqosah form fields shared by create (index) and edit. Expects: $munaqosah (null on create), $generus, $academicYears, $semesters. --}}
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
    <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($generus as $item)
            <option value="{{ $item->id }}" @selected(old('generus_id', $munaqosah?->generus_id) == $item->id)>{{ $item->full_name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun</label>
    <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($academicYears as $year)
            <option value="{{ $year->id }}" @selected(old('academic_year_id', $munaqosah?->academic_year_id) == $year->id)>{{ $year->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="semester_id">Semester</label>
    <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($semesters as $semester)
            <option value="{{ $semester->id }}" @selected(old('semester_id', $munaqosah?->semester_id) == $semester->id)>{{ $semester->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="title">Judul</label>
    <input id="title" name="title" type="text" value="{{ old('title', $munaqosah?->title) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="type">Tipe</label>
    <select id="type" name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="semester-final" @selected(old('type', $munaqosah?->type) === 'semester-final')>Semester Final</option>
        <option value="remedial" @selected(old('type', $munaqosah?->type) === 'remedial')>Remedial</option>
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="score">Nilai</label>
    <input id="score" name="score" type="number" step="0.01" min="0" max="100" value="{{ old('score', $munaqosah?->score) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="result">Hasil (huruf)</label>
    <input id="result" name="result" type="text" value="{{ old('result', $munaqosah?->result) }}" placeholder="Kosongkan untuk konversi otomatis dari nilai" class="w-full rounded-lg border border-slate-300 px-3 py-2">
    <p class="mt-1 text-xs text-slate-500">Jika dikosongkan, huruf dan keterangan diambil otomatis dari <a href="{{ route('master-data.grade-scales.index') }}" class="text-amber-600 underline">master data Konversi Nilai</a> sesuai nilai di atas.</p>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="status">Status</label>
    <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="scheduled" @selected(old('status', $munaqosah?->status) === 'scheduled')>Terjadwal</option>
        <option value="completed" @selected(old('status', $munaqosah?->status) === 'completed')>Selesai</option>
        <option value="failed" @selected(old('status', $munaqosah?->status) === 'failed')>Gagal</option>
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
    <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes', $munaqosah?->notes) }}</textarea>
</div>
