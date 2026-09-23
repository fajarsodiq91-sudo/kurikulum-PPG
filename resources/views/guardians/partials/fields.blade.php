{{-- Guardian form fields shared by create (index) and edit. Expects: $guardian (null on create). --}}
<div class="space-y-4">
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="full_name">Nama Lengkap <span class="text-rose-500">*</span></label>
        <input id="full_name" name="full_name" type="text" required value="{{ old('full_name', $guardian?->full_name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="relationship">Hubungan <span class="text-rose-500">*</span></label>
        <select id="relationship" name="relationship" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
            <option value="">-- Pilih --</option>
            @foreach (['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali'] as $value => $label)
                <option value="{{ $value }}" @selected(old('relationship', $guardian?->relationship) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="phone">Telepon</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $guardian?->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="address">Alamat</label>
        <textarea id="address" name="address" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('address', $guardian?->address) }}</textarea>
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
            <option value="active" @selected(old('status', $guardian?->status ?? 'active') === 'active')>Aktif</option>
            <option value="inactive" @selected(old('status', $guardian?->status) === 'inactive')>Nonaktif</option>
        </select>
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="notes">Catatan</label>
        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('notes', $guardian?->notes) }}</textarea>
    </div>
</div>
