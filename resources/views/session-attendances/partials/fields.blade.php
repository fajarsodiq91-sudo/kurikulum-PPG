{{-- Attendance form fields shared by create (index) and edit. Expects: $attendance (null on create), $sessions, $generus. --}}
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="learning_session_id">Sesi</label>
    <select id="learning_session_id" name="learning_session_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($sessions as $session)
            <option value="{{ $session->id }}" @selected(old('learning_session_id', $attendance?->learning_session_id) == $session->id)>{{ $session->session_date }} - {{ $session->teacher?->name ?? 'Guru' }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="generus_id">Generus</label>
    <select id="generus_id" name="generus_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        <option value="">-- Pilih --</option>
        @foreach($generus as $entry)
            <option value="{{ $entry->id }}" @selected(old('generus_id', $attendance?->generus_id) == $entry->id)>{{ $entry->full_name }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="status">Status</label>
    <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        @foreach (['present' => 'Hadir', 'late' => 'Terlambat', 'absent' => 'Absen', 'excused' => 'Izin'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $attendance?->status ?? 'present') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="mb-4">
    <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
    <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes', $attendance?->notes) }}</textarea>
</div>
