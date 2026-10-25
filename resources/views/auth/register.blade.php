<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar Akun | PPG</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ppg-karawang-timur.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
            <div class="mb-6 flex items-center gap-3">
                <img src="{{ asset('images/logo-ppg-karawang-timur.png') }}" alt="Logo PPG Karawang Timur" class="h-12 w-12 object-contain">
                <div>
                    <p class="text-sm font-bold text-slate-800">PPG Karawang Timur</p>
                    <p class="text-xs text-slate-500">Management & Learning Monitoring System</p>
                </div>
            </div>

            <h1 class="text-2xl font-bold text-slate-800 mb-2">Daftar Akun</h1>
            <p class="mb-6 text-sm text-slate-500">Akun Google <span class="font-semibold text-slate-700">{{ $registration['email'] }}</span> belum terdaftar. Lengkapi data berikut untuk mengajukan akun. Admin akan meninjau dan menyetujui sebelum akun bisa digunakan.</p>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2" for="name">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $registration['name']) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email Google</label>
                    <input type="text" value="{{ $registration['email'] }}" disabled class="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-slate-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2" for="role_id">Peran yang Diajukan</label>
                    <select id="role_id" name="role_id" required data-register-role class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none">
                        <option value="">-- Pilih peran --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" data-scope="{{ $roleScopes[$role->slug] ?? 'global' }}" @selected((string) old('role_id') === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4 hidden" data-register-scope="village">
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
                        <option value="">-- Pilih kelompok --</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((string) old('group_id') === (string) $group->id)>{{ $group->village?->name }} / {{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    Ajukan Pendaftaran
                </button>
            </form>

            <a href="{{ route('login') }}" class="mt-4 block text-center text-sm text-slate-500 hover:text-slate-700">Batal, kembali ke halaman masuk</a>
        </div>
    </div>
    <script>
        const roleSelect = document.querySelector('[data-register-role]');
        const scopeFields = document.querySelectorAll('[data-register-scope]');

        function refreshScope() {
            const selected = roleSelect.options[roleSelect.selectedIndex];
            const scope = selected ? selected.dataset.scope : null;

            scopeFields.forEach((field) => {
                const matches = field.dataset.registerScope === scope;
                field.classList.toggle('hidden', !matches);
                field.querySelector('select').disabled = !matches;
            });
        }

        roleSelect.addEventListener('change', refreshScope);
        refreshScope();
    </script>
</body>
</html>
