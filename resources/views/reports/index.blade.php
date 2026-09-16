<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan | PPG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen p-8">
        <div class="max-w-6xl mx-auto">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Laporan</h1>
                    <p class="text-slate-500">Ringkasan capaian program PPG</p>
                </div>
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">Kembali ke Dashboard</a>
            </div>

            <div class="grid gap-4 md:grid-cols-3 mb-8">
                <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-500">Generus</p>
                    <p class="mt-2 text-3xl font-bold">{{ $generusCount }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-500">Guru</p>
                    <p class="mt-2 text-3xl font-bold">{{ $teacherCount }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm border border-slate-200">
                    <p class="text-sm text-slate-500">Pelatihan</p>
                    <p class="mt-2 text-3xl font-bold">{{ $trainingCount }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
                <h2 class="text-xl font-semibold mb-4">Jadwal Pelatihan Terbaru</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 pr-4">Judul</th>
                                <th class="py-3 pr-4">Tahun</th>
                                <th class="py-3 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trainings as $training)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4">{{ $training->title }}</td>
                                    <td class="py-3 pr-4">{{ $training->academicYear?->name ?? '-' }}</td>
                                    <td class="py-3 pr-4">{{ $training->status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">Belum ada data pelatihan.</td>
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
