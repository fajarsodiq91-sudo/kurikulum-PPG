{{-- Evaluation score form fields shared by create (index) and edit. Expects: $score (null on create), $evaluations, $generus. --}}
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="evaluation_id">Evaluasi</label>
    <select id="evaluation_id" name="evaluation_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($evaluations as $evaluation)
            <option value="{{ $evaluation->id }}" @selected(old('evaluation_id', $score?->evaluation_id) == $evaluation->id)>{{ $evaluation->title }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
    <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($generus as $entry)
            <option value="{{ $entry->id }}" @selected(old('generus_id', $score?->generus_id) == $entry->id)>{{ $entry->full_name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="score">Skor</label>
    <input id="score" name="score" type="number" min="0" max="100" step="0.1" required value="{{ old('score', $score?->score) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="grade">Grade</label>
    <select id="grade" name="grade" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        @foreach (['A', 'B', 'C', 'D', 'E'] as $grade)
            <option value="{{ $grade }}" @selected(old('grade', $score?->grade) === $grade)>{{ $grade }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
    <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes', $score?->notes) }}</textarea>
</div>
