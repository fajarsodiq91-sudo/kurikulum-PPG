<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Guru</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold">Data Guru</h1>
                <p class="text-slate-500">Kelola tenaga pendidik</p>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Guru</h2>

                <form method="POST" action="{{ route('teachers.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="name">Nama</label>
                        <input id="name" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="gender">Jenis Kelamin</label>
                        <select id="gender" name="gender" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            <option value="laki-laki">Laki-laki</option>
                            <option value="perempuan">Perempuan</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="phone">Telepon</label>
                        <input id="phone" name="phone" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="email">Email</label>
                        <input id="email" name="email" type="email" class="w-full rounded-lg border border-slate-300 px-3 py-2">
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
                <h2 class="text-xl font-semibold mb-4">Daftar Guru</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Nama</th>
                                <th class="py-3 pr-4">Telepon</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teachers as $teacher)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $teacher->name }}</td>
                                    <td class="py-3 pr-4">{{ $teacher->phone ?? '-' }}</td>
                                    <td class="py-3 pr-4">
                                        @if($teacher->status === 'active')
                                            <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-medium text-slate-600">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada data guru.</td>
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
