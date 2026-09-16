<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PPG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen p-8">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold">Dashboard</h1>
                    <p class="text-slate-500">Selamat datang di sistem PPG Management</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('reports.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700">Laporan</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white">Logout</button>
                    </form>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
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
        </div>
    </div>
</body>
</html>
