<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KBM / Jadwal Pembelajaran</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold">Jadwal KBM</h1>
                <p class="text-slate-500">Kelola sesi pembelajaran dan pengajar</p>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Sesi</h2>
                <form method="POST" action="{{ route('learning-sessions.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="teacher_id">Guru</label>
                        <select id="teacher_id" name="teacher_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="material_id">Materi</label>
                        <select id="material_id" name="material_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($materials as $material)
                                <option value="{{ $material->id }}">{{ $material->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="session_date">Tanggal</label>
                        <input id="session_date" name="session_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="start_time">Jam Mulai</label>
                            <input id="start_time" name="start_time" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2" for="end_time">Jam Selesai</label>
                            <input id="end_time" name="end_time" type="time" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="location">Lokasi</label>
                        <input id="location" name="location" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="scheduled">Terjadwal</option>
                            <option value="completed">Selesai</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Sesi</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Tanggal</th>
                                <th class="py-3 pr-4">Guru</th>
                                <th class="py-3 pr-4">Materi</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $session->session_date }}</td>
                                    <td class="py-3 pr-4">{{ $session->teacher?->name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $session->material?->title ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $session->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-slate-500">Belum ada sesi KBM.</td>
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
