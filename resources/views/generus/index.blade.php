<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generus</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="max-w-6xl mx-auto py-10 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold">Generus</h1>
                <p class="text-slate-500">Master identitas dan riwayat penempatan</p>
            </div>
            <a href="{{ route('generus.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Tambah Generus</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl bg-white p-6 shadow-sm border border-slate-200">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="py-3 pr-4">Nomor Induk</th>
                        <th class="py-3 pr-4">Nama</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4">Assignment</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($generus as $item)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4">{{ $item->registration_number }}</td>
                            <td class="py-3 pr-4">{{ $item->full_name }}</td>
                            <td class="py-3 pr-4">{{ $item->status }}</td>
                            <td class="py-3 pr-4">
                                @foreach($item->assignments as $assignment)
                                    <div class="text-xs text-slate-600">
                                        {{ $assignment->region?->name ?? '-' }} / {{ $assignment->village?->name ?? '-' }} / {{ $assignment->group?->name ?? '-' }}
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-500">Belum ada data generus.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
