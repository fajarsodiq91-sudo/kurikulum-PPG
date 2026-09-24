{{-- Teacher form fields shared by create (index) and edit. Expects: $teacher (null on create). --}}
<div class="space-y-4">
    @if ($teacher)
        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700" for="registration_number">Nomor Induk Guru</label>
            <input id="registration_number" type="text" readonly value="{{ $teacher->registration_number }}" class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-sm text-slate-600">
        </div>
    @endif
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Nama <span class="text-rose-500">*</span></label>
        <input id="name" name="name" type="text" required value="{{ old('name', $teacher?->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="gender">Jenis Kelamin</label>
        <select id="gender" name="gender" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
            <option value="">-- Pilih --</option>
            <option value="laki-laki" @selected(old('gender', $teacher?->gender) === 'laki-laki')>Laki-laki</option>
            <option value="perempuan" @selected(old('gender', $teacher?->gender) === 'perempuan')>Perempuan</option>
        </select>
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="phone">Telepon</label>
        <input id="phone" name="phone" type="text" value="{{ old('phone', $teacher?->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $teacher?->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="photo">Foto</label>
        <div class="flex items-center gap-4">
            @if ($photoDataUri = $teacher?->photoDataUri())
                <img src="{{ $photoDataUri }}" alt="Foto {{ $teacher->name }}" class="h-20 w-15 shrink-0 rounded-lg object-cover ring-1 ring-slate-200">
            @endif
            <div class="min-w-0 flex-1">
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP maksimal 2 MB, rasio 3:4. {{ $teacher?->photo ? 'Kosongkan jika tidak ingin mengganti foto.' : 'Dipakai pada ID card.' }}</p>
            </div>
        </div>
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
            <option value="active" @selected(old('status', $teacher?->status ?? 'active') === 'active')>Aktif</option>
            <option value="inactive" @selected(old('status', $teacher?->status) === 'inactive')>Nonaktif</option>
        </select>
    </div>
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="notes">Catatan</label>
        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">{{ old('notes', $teacher?->notes) }}</textarea>
    </div>
</div>
