<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Orang Tua/Wali</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold">Data Orang Tua/Wali</h1>
                <p class="text-slate-500">Kelola keterkaitan keluarga generus</p>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Wali</h2>

                <form method="POST" action="{{ route('guardians.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="full_name">Nama Lengkap</label>
                        <input id="full_name" name="full_name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="relationship">Hubungan</label>
                        <select id="relationship" name="relationship" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            <option value="ayah">Ayah</option>
                            <option value="ibu">Ibu</option>
                            <option value="wali">Wali</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="phone">Telepon</label>
                        <input id="phone" name="phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="address">Alamat</label>
                        <textarea id="address" name="address" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        Simpan
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Orang Tua/Wali</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Nama</th>
                                <th class="py-3 pr-4">Hubungan</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($guardians as $guardian)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $guardian->full_name }}</td>
                                    <td class="py-3 pr-4">{{ $guardian->relationship }}</td>
                                    <td class="py-3 pr-4">
                                        @if($guardian->status === 'active')
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada data orang tua/wali.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
