<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelaksanaan Kegiatan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Pelaksanaan Kegiatan</h1>
            <p class="text-slate-500">Catat realisasi, kehadiran, dan hasil kegiatan</p>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-xl font-semibold">Catat Pelaksanaan</h2>
                <form method="POST" action="{{ route('activity-executions.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium" for="activity_schedule_id">Jadwal Kegiatan</label>
                        <select id="activity_schedule_id" name="activity_schedule_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">-- Pilih --</option>
                            @foreach($activitySchedules as $schedule)
                                <option value="{{ $schedule->id }}">{{ $schedule->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium" for="actual_date">Tanggal Pelaksanaan</label>
                        <input id="actual_date" name="actual_date" type="date" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium" for="status">Status</label>
                        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="completed">Selesai</option>
                            <option value="partial">Sebagian</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium" for="attendance_count">Jumlah Hadir</label>
                        <input id="attendance_count" name="attendance_count" type="number" min="0" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium" for="outcome">Hasil Kegiatan</label>
                        <textarea id="outcome" name="outcome" rows="3" required class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium" for="notes">Catatan</label>
                        <textarea id="notes" name="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Simpan</button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="mb-4 text-xl font-semibold">Riwayat Pelaksanaan</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead><tr class="border-b border-slate-200"><th class="py-3 pr-4">Kegiatan</th><th class="py-3 pr-4">Tanggal</th><th class="py-3 pr-4">Status</th><th class="py-3 pr-4">Hadir</th></tr></thead>
                        <tbody>
                            @forelse($activityExecutions as $execution)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $execution->activitySchedule?->title ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $execution->actual_date }}</td>
                                    <td class="py-3 pr-4">{{ $execution->status }}</td>
                                    <td class="py-3 pr-4">{{ $execution->attendance_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-slate-500">Belum ada pelaksanaan kegiatan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
