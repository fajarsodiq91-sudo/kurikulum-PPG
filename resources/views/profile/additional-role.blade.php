@extends('layouts.app')

@section('title', 'Ajukan Dapukan Tambahan')
@section('page-title', 'Ajukan Dapukan Tambahan')
@section('breadcrumb', 'Akun / Ajukan Dapukan Tambahan')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Akun</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Ajukan Dapukan Tambahan</h2>
        <p class="mt-2 text-sm text-slate-500">Pilih peran tambahan di bawah peran Anda saat ini. Pengajuan ini akan menunggu persetujuan admin sebelum aktif.</p>
    </div>

    <section class="max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($roles->isEmpty())
            <x-empty-state title="Tidak ada peran yang tersedia" description="Tidak ada peran tambahan yang bisa diajukan dari peran Anda saat ini." class="border-none bg-transparent" />
        @else
            <form method="POST" action="{{ route('profile.additional-role.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2" for="role_id">Peran yang Diajukan</label>
                    <select id="role_id" name="role_id" required data-register-role class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none">
                        <option value="">-- Pilih peran --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" data-scope="{{ $roleScopes[$role->slug] ?? 'global' }}" data-role-slug="{{ $role->slug }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                @foreach ($roles as $role)
                    <div class="mb-4 hidden" data-register-dapukan="{{ $role->slug }}">
                        <label class="block text-sm font-medium text-slate-700 mb-2" for="dapukan_id_{{ $role->slug }}">Dapukan</label>
                        <select id="dapukan_id_{{ $role->slug }}" name="dapukan_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none">
                            <option value="">-- Pilih dapukan --</option>
                            @foreach (($dapukansByRole[$role->slug] ?? collect())->groupBy('group_label') as $groupLabel => $dapukans)
                                @if ($groupLabel)
                                    <optgroup label="{{ $groupLabel }}">
                                        @foreach ($dapukans as $dapukan)
                                            <option value="{{ $dapukan->id }}" @selected((string) old('dapukan_id') === (string) $dapukan->id)>{{ $dapukan->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @else
                                    @foreach ($dapukans as $dapukan)
                                        <option value="{{ $dapukan->id }}" @selected((string) old('dapukan_id') === (string) $dapukan->id)>{{ $dapukan->name }}</option>
                                    @endforeach
                                @endif
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="mb-4 hidden" data-register-scope="village group">
                    <label class="block text-sm font-medium text-slate-700 mb-2" for="village_id">Desa</label>
                    <select id="village_id" name="village_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none">
                        <option value="">-- Pilih desa --</option>
                        @foreach ($villages as $village)
                            <option value="{{ $village->id }}" @selected((string) old('village_id') === (string) $village->id)>{{ $village->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6 hidden" data-register-scope="group">
                    <label class="block text-sm font-medium text-slate-700 mb-2" for="group_id">Kelompok</label>
                    <select id="group_id" name="group_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none">
                        <option value="">-- Pilih desa terlebih dahulu --</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" data-village-id="{{ $group->village_id }}" @selected((string) old('group_id') === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full rounded-lg bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                    Ajukan Peran Tambahan
                </button>
            </form>
        @endif

        <a href="{{ route('profile.edit') }}" class="mt-4 block text-center text-sm text-slate-500 hover:text-slate-700">Batal, kembali ke profil</a>
    </section>

    @if ($roles->isNotEmpty())
        <script>
            const roleSelect = document.querySelector('[data-register-role]');
            const scopeFields = document.querySelectorAll('[data-register-scope]');
            const dapukanFields = document.querySelectorAll('[data-register-dapukan]');
            const villageSelect = document.getElementById('village_id');
            const groupSelect = document.getElementById('group_id');
            const groupOptions = groupSelect ? Array.from(groupSelect.options).filter((option) => option.value !== '') : [];

            function refreshGroupOptions() {
                if (!groupSelect) {
                    return;
                }

                const villageId = villageSelect ? villageSelect.value : '';
                const currentValue = groupSelect.value;

                groupSelect.innerHTML = '';
                groupSelect.appendChild(new Option(villageId ? '-- Pilih kelompok --' : '-- Pilih desa terlebih dahulu --', ''));

                groupOptions
                    .filter((option) => option.dataset.villageId === villageId)
                    .forEach((option) => groupSelect.appendChild(new Option(option.text, option.value)));

                groupSelect.value = Array.from(groupSelect.options).some((option) => option.value === currentValue) ? currentValue : '';
            }

            function refreshScope() {
                const selected = roleSelect.options[roleSelect.selectedIndex];
                const scope = selected ? selected.dataset.scope : null;
                const roleSlug = selected ? selected.dataset.roleSlug : null;

                scopeFields.forEach((field) => {
                    const matches = (field.dataset.registerScope || '').split(' ').includes(scope);
                    field.classList.toggle('hidden', !matches);
                    field.querySelector('select').disabled = !matches;
                });

                dapukanFields.forEach((field) => {
                    const matches = field.dataset.registerDapukan === roleSlug;
                    field.classList.toggle('hidden', !matches);
                    field.querySelector('select').disabled = !matches;
                });

                if (groupSelect) {
                    groupSelect.disabled = groupSelect.disabled || !villageSelect.value;
                }

                refreshGroupOptions();
            }

            roleSelect.addEventListener('change', refreshScope);
            if (villageSelect) {
                villageSelect.addEventListener('change', refreshGroupOptions);
            }
            refreshScope();
        </script>
    @endif
@endsection
