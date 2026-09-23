<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penugasan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold">Penugasan & Tanggung Jawab</h1>
                <p class="text-slate-500">Kelola tugas guru dan unit organisasi</p>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-1 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Tambah Penugasan</h2>
                <form method="POST" action="{{ route('assignments.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="organization_unit_id">Unit Organisasi</label>
                        <select id="organization_unit_id" name="organization_unit_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($organizationUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
                        <label class="block text-sm font-medium mb-2" for="assignment_title">Judul Penugasan</label>
                        <input id="assignment_title" name="assignment_title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="assignment_type">Tipe</label>
                        <select id="assignment_type" name="assignment_type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="kurikulum">Kurikulum</option>
                            <option value="pembinaan">Pembinaan</option>
                            <option value="administrasi">Administrasi</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="start_date">Tanggal Mulai</label>
                        <input id="start_date" name="start_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="end_date">Tanggal Selesai</label>
                        <input id="end_date" name="end_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="active">Aktif</option>
                            <option value="completed">Selesai</option>
                            <option value="paused">Dijeda</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2" for="responsibility">Tanggung Jawab</label>
                        <textarea id="responsibility" name="responsibility" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                </form>
            </div>

            <div class="lg:col-span-2 rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Daftar Penugasan</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Judul</th>
                                <th class="py-3 pr-4">Unit</th>
                                <th class="py-3 pr-4">Guru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $assignment->assignment_title }}</td>
                                    <td class="py-3 pr-4">{{ $assignment->organizationUnit?->name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $assignment->teacher?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada penugasan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $assignments->links() }}</div>
            </div>
        </div>
    </div>
</body>
</html>
