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
        <label class="mb-2 block text-sm font-medium text-slate-700">Peran dan Cakupan</label>
        <p class="mb-2 text-xs text-slate-500">Centang peran, lalu tentukan cakupannya: seluruh sistem, satu desa, atau satu kelompok.</p>
        <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-3" data-role-scopes>
            @foreach ($roles as $role)
                @php
                    $pivot = $user?->roles->firstWhere('id', $role->id)?->pivot;
                    $checked = in_array($role->id, old('roles', $user?->roles->pluck('id')->all() ?? []));
                    $scopeType = old("role_scopes.{$role->id}.type", in_array($pivot?->scope_type, ['village', 'group'], true) ? $pivot->scope_type : 'global');
                    $scopeId = old("role_scopes.{$role->id}.id", $pivot?->scope_id);
                @endphp
                <div class="role-row" data-role-row>
                    <label class="flex items-center gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($checked) class="role-checkbox h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        <span>{{ $role->name }}</span>
                        @if ($pivot && ! in_array($pivot->scope_type, ['global', 'village', 'group', null], true))
                            <span class="text-xs text-slate-400">(cakupan khusus: {{ $pivot->scope_type }})</span>
                        @endif
                    </label>
                    <div class="scope-fields mt-2 hidden gap-2 pl-7 sm:grid-cols-2" @if (! $pivot || in_array($pivot->scope_type, ['global', 'village', 'group', null], true)) data-editable @endif>
                        <select name="role_scopes[{{ $role->id }}][type]" class="scope-type w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="global" @selected($scopeType === 'global')>Seluruh sistem</option>
                            <option value="village" @selected($scopeType === 'village')>Satu desa</option>
                            <option value="group" @selected($scopeType === 'group')>Satu kelompok</option>
                        </select>
                        <select name="role_scopes[{{ $role->id }}][id]" class="scope-village hidden w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-scope="village" disabled>
                            <option value="">-- Pilih desa --</option>
                            @foreach ($villages as $village)
                                <option value="{{ $village->id }}" @selected($scopeType === 'village' && (int) $scopeId === $village->id)>{{ $village->name }}</option>
                            @endforeach
                        </select>
                        <select name="role_scopes[{{ $role->id }}][id]" class="scope-group hidden w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-scope="group" disabled>
                            <option value="">-- Pilih kelompok --</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" @selected($scopeType === 'group' && (int) $scopeId === $group->id)>{{ $group->village?->name }} / {{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('[data-role-row]').forEach((row) => {
        const checkbox = row.querySelector('.role-checkbox');
        const fields = row.querySelector('.scope-fields');
        const type = row.querySelector('.scope-type');
        const village = row.querySelector('.scope-village');
        const group = row.querySelector('.scope-group');

        function refresh() {
            const editable = fields.hasAttribute('data-editable');
            fields.classList.toggle('hidden', !checkbox.checked || !editable);
            fields.classList.toggle('grid', checkbox.checked && editable);
            type.disabled = !editable;
            village.classList.toggle('hidden', type.value !== 'village');
            group.classList.toggle('hidden', type.value !== 'group');
            village.disabled = !editable || type.value !== 'village' || !checkbox.checked;
            group.disabled = !editable || type.value !== 'group' || !checkbox.checked;
        }

        checkbox.addEventListener('change', refresh);
        type.addEventListener('change', refresh);
        refresh();
    });
</script>
