<div class="space-y-4">
    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Nama <span class="text-rose-500">*</span></label>
        <input id="name" name="name" type="text" required value="{{ old('name', $user?->name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="email">Email <span class="text-rose-500">*</span></label>
        <input id="email" name="email" type="email" required value="{{ old('email', $user?->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="password">Kata Sandi @if (! $user)<span class="text-rose-500">*</span>@endif</label>
        <input id="password" name="password" type="password" @if (! $user) required @endif placeholder="{{ $user ? 'Kosongkan jika tidak ingin mengubah' : 'Minimal 8 karakter' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-100">
            <option value="active" @selected(old('status', $user?->status ?? 'active') === 'active')>Aktif</option>
            <option value="inactive" @selected(old('status', $user?->status) === 'inactive')>Nonaktif</option>
        </select>
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-slate-700">Peran</label>
        <div class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
            @foreach ($roles as $role)
                <label class="flex items-center gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, old('roles', $user?->roles->pluck('id')->all() ?? []))) class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                    <span>{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
