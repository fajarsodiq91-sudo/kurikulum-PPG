@extends('layouts.app')

@section('title', 'Tugas')
@section('page-title', 'Tugas')
@section('breadcrumb', 'Tugas')

@section('content')
    @php
        $statusMeta = [
            'todo' => ['label' => 'Belum Dikerjakan', 'next' => 'in_progress', 'nextLabel' => 'Mulai →'],
            'in_progress' => ['label' => 'Sedang Dikerjakan', 'next' => 'done', 'nextLabel' => 'Selesai →'],
            'done' => ['label' => 'Selesai', 'next' => null, 'nextLabel' => null],
        ];
        $priorityMeta = [
            'high' => ['label' => 'Tinggi', 'class' => 'bg-rose-100 text-rose-700'],
            'medium' => ['label' => 'Sedang', 'class' => 'bg-amber-100 text-amber-700'],
            'low' => ['label' => 'Rendah', 'class' => 'bg-slate-100 text-slate-600'],
        ];
        $user = auth()->user();
    @endphp

    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Tugas</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Papan Tugas</h2>
        <p class="mt-2 text-sm text-slate-500">Catat dan pantau tugas atau action plan hasil musyawarah/rapat pengurus, sesuai hirarki kepengurusan.</p>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach ($statusMeta as $key => $meta)
            <x-card class="p-4">
                <p class="text-xs font-medium text-slate-500">{{ $meta['label'] }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-950">{{ $tasksByStatus->get($key, collect())->count() }}</p>
            </x-card>
        @endforeach
    </div>

    @if (\App\Support\Access::can('manage-tasks'))
        <x-card class="mb-6">
            <h2 class="mb-4 text-xl font-semibold">Tambah Tugas</h2>
            <form method="POST" action="{{ route('tasks.store') }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2" for="title">Judul Tugas</label>
                    <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('title') }}">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2" for="description">Deskripsi / Catatan Rapat</label>
                    <textarea id="description" name="description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" for="bucket">Kategori / Sumber (misal nama rapat)</label>
                    <input id="bucket" name="bucket" type="text" placeholder="Hasil Rapat Pengurus Oktober 2026" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('bucket') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" for="priority">Prioritas</label>
                    <select id="priority" name="priority" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="low" @selected(old('priority') === 'low')>Rendah</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>Sedang</option>
                        <option value="high" @selected(old('priority') === 'high')>Tinggi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" for="due_date">Jatuh Tempo</label>
                    <input id="due_date" name="due_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('due_date') }}">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2" for="assignees">Ditugaskan Kepada</label>
                    <select id="assignees" name="assignees[]" multiple required size="6" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        @foreach ($recipients as $recipient)
                            <option value="{{ $recipient->id }}">{{ $recipient->name }}@if ($recipient->roles->isNotEmpty()) ({{ $recipient->roles->pluck('name')->join(', ') }})@endif</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Tahan Ctrl/Cmd untuk memilih lebih dari satu penerima. Hanya pengurus di jalur vertikal Anda (atasan dan cabang Anda sendiri) yang bisa dipilih.</p>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan Tugas</button>
                </div>
            </form>
        </x-card>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($statusMeta as $statusKey => $meta)
            <div>
                <h3 class="mb-3 flex items-center justify-between text-sm font-semibold text-slate-700">
                    <span>{{ $meta['label'] }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $tasksByStatus->get($statusKey, collect())->count() }}</span>
                </h3>
                <div class="space-y-3">
                    @forelse ($tasksByStatus->get($statusKey, collect()) as $task)
                        <x-card class="p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="text-sm font-semibold text-slate-900">{{ $task->title }}</h4>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $priorityMeta[$task->priority]['class'] ?? 'bg-slate-100 text-slate-600' }}">{{ $priorityMeta[$task->priority]['label'] ?? $task->priority }}</span>
                            </div>
                            @if ($task->bucket)
                                <p class="mt-1 text-xs font-medium text-amber-600">{{ $task->bucket }}</p>
                            @endif
                            @if ($task->description)
                                <p class="mt-2 text-xs leading-5 text-slate-500">{{ $task->description }}</p>
                            @endif
                            <p class="mt-3 text-xs text-slate-500">Untuk: <span class="font-medium text-slate-700">{{ $task->assignees->pluck('name')->join(', ') }}</span></p>
                            <p class="mt-1 text-xs text-slate-500">Dari: <span class="font-medium text-slate-700">{{ $task->assignedBy?->name ?? '-' }}</span></p>
                            @if ($task->due_date)
                                <p class="mt-1 text-xs text-slate-500">Jatuh tempo: {{ $task->due_date->translatedFormat('d M Y') }}</p>
                            @endif

                            <div class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                                @if ($task->isEditableBy($user))
                                    @if ($meta['next'])
                                        <form method="POST" action="{{ route('tasks.update-status', $task) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $meta['next'] }}">
                                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $meta['nextLabel'] }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('tasks.update-status', $task) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="todo">
                                            <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Buka kembali</button>
                                        </form>
                                    @endif
                                @endif
                                @if ($task->isEditableBy($user))
                                    <a href="{{ route('tasks.edit', $task) }}" class="ml-auto rounded-lg px-2 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-900">Ubah</a>
                                @endif
                            </div>
                        </x-card>
                    @empty
                        <x-empty-state title="Belum ada tugas." class="border-none bg-transparent" />
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@endsection
