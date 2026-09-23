{{-- Report card form fields shared by create (index) and edit. Expects: $reportCard (null on create), $generus, $academicYears, $semesters. --}}
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
    <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($generus as $item)
            <option value="{{ $item->id }}" @selected(old('generus_id', $reportCard?->generus_id) == $item->id)>{{ $item->full_name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="academic_year_id">Tahun</label>
    <select id="academic_year_id" name="academic_year_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($academicYears as $year)
            <option value="{{ $year->id }}" @selected(old('academic_year_id', $reportCard?->academic_year_id) == $year->id)>{{ $year->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="semester_id">Semester</label>
    <select id="semester_id" name="semester_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($semesters as $semester)
            <option value="{{ $semester->id }}" @selected(old('semester_id', $reportCard?->semester_id) == $semester->id)>{{ $semester->name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="final_score">Nilai Akhir</label>
    <input id="final_score" name="final_score" type="number" step="0.01" value="{{ old('final_score', $reportCard?->final_score) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="predicate">Predikat</label>
    <input id="predicate" name="predicate" type="text" value="{{ old('predicate', $reportCard?->predicate) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="recommendation">Rekomendasi</label>
    <textarea id="recommendation" name="recommendation" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('recommendation', $reportCard?->recommendation) }}</textarea>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="remarks">Catatan</label>
    <textarea id="remarks" name="remarks" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('remarks', $reportCard?->remarks) }}</textarea>
</div>
