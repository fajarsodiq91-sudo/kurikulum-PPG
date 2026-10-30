@extends('layouts.app')

@section('title', 'Tugas')
@section('page-title', 'Tugas')
@section('breadcrumb', 'Tugas')

@section('content')
    @php
        $statusMeta = [
            'todo' => ['label' => 'Belum Dikerjakan', 'next' => 'in_progress', 'nextLabel' => 'Mulai →', 'class' => 'bg-slate-100 text-slate-600'],
            'in_progress' => ['label' => 'Sedang Dikerjakan', 'next' => 'done', 'nextLabel' => 'Selesai →', 'class' => 'bg-sky-100 text-sky-700'],
            'done' => ['label' => 'Selesai', 'next' => null, 'nextLabel' => null, 'class' => 'bg-emerald-100 text-emerald-700'],
        ];
        $priorityMeta = [
            'high' => ['label' => 'Tinggi', 'class' => 'bg-rose-100 text-rose-700'],
            'medium' => ['label' => 'Sedang', 'class' => 'bg-amber-100 text-amber-700'],
            'low' => ['label' => 'Rendah', 'class' => 'bg-slate-100 text-slate-600'],
        ];
        $user = auth()->user();
        $totalTasks = $tasksByStatus->flatten()->count();
        $doneCount = $tasksByStatus->get('done', collect())->count();
        $progressPercent = $totalTasks > 0 ? (int) round($doneCount / $totalTasks * 100) : 0;
        $formShouldOpen = $errors->any();
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Tugas</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Papan Tugas</h2>
            <p class="mt-2 text-sm text-slate-500">Catat dan pantau tugas atau action plan hasil musyawarah/rapat pengurus, sesuai hirarki kepengurusan.</p>
        </div>
        @if (\App\Support\Access::can('manage-tasks'))
            <button type="button" id="open-create-task" class="inline-flex shrink-0 items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                + Tambah Tugas
            </button>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach ($statusMeta as $key => $meta)
            <x-card class="p-4">
                <p class="text-xs font-medium text-slate-500">{{ $meta['label'] }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-950">{{ $tasksByStatus->get($key, collect())->count() }}</p>
            </x-card>
        @endforeach
    </div>

    <x-card class="mb-6 p-4">
        <div class="flex items-center justify-between text-xs font-medium text-slate-500">
            <span>Progres Keseluruhan</span>
            <span>{{ $doneCount }} / {{ $totalTasks }} selesai ({{ $progressPercent }}%)</span>
        </div>
        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $progressPercent }}%"></div>
        </div>
    </x-card>

    @if (\App\Support\Access::can('manage-tasks'))
        <div id="create-task-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $formShouldOpen ? '' : 'hidden' }}" data-create-task-close></div>

        <div id="create-task-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $formShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-task-title">
            <div class="mx-auto my-6 w-full max-w-2xl">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-task-close>&times;</button>
                    <h2 id="create-task-title" class="mb-4 text-xl font-semibold">Tambah Tugas</h2>
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
                            <span class="mb-2 block text-sm font-medium text-slate-700">Ditugaskan Kepada</span>
                            @include('tasks.partials.assignee-picker', ['recipients' => $recipients, 'selectedIds' => old('assignees', [])])
                            <p class="mt-1 text-xs text-slate-500">Hanya pengurus di jalur vertikal Anda (atasan dan cabang Anda sendiri) yang bisa dipilih.</p>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan Tugas</button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    @endif

    @if ($totalTasks > 0)
        <x-card class="mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                Prioritas
                <select id="filter-priority" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach ($priorityMeta as $key => $meta)
                        <option value="{{ $key }}">{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                Petugas
                <select id="filter-assignee" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach ($assigneeOptions as $assignee)
                        <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="button" id="filter-reset" class="text-sm font-medium text-slate-500 hover:text-slate-800 sm:ml-auto">Hapus Filter</button>
        </x-card>
    @endif

    <div class="space-y-8">
        @forelse ($tasksByBucket as $bucket => $bucketTasks)
            <div data-bucket-section>
                <h3 class="mb-3 flex items-center justify-between text-sm font-semibold text-slate-700">
                    <span>{{ $bucket }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $bucketTasks->count() }}</span>
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($bucketTasks as $task)
                        @php
                            $meta = $statusMeta[$task->status] ?? null;
                            $isOverdue = $task->due_date && $task->status !== 'done' && $task->due_date->isPast();
                        @endphp
                        <x-card
                            class="task-card p-4"
                            data-priority="{{ $task->priority }}"
                            data-assignee-ids="{{ $task->assignees->pluck('id')->implode(',') }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="text-sm font-semibold text-slate-900">{{ $task->title }}</h4>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $priorityMeta[$task->priority]['class'] ?? 'bg-slate-100 text-slate-600' }}">{{ $priorityMeta[$task->priority]['label'] ?? $task->priority }}</span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                @if ($meta)
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                @endif
                                @if ($isOverdue)
                                    <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700">Terlambat</span>
                                @endif
                            </div>
                            @if ($task->description)
                                <p class="mt-2 text-xs leading-5 text-slate-500">{{ $task->description }}</p>
                            @endif
                            <p class="mt-3 text-xs text-slate-500">Untuk: <span class="font-medium text-slate-700">{{ $task->assignees->pluck('name')->join(', ') }}</span></p>
                            <p class="mt-1 text-xs text-slate-500">Dari: <span class="font-medium text-slate-700">{{ $task->assignedBy?->name ?? '-' }}</span></p>
                            @if ($task->due_date)
                                <p class="mt-1 text-xs {{ $isOverdue ? 'font-medium text-rose-600' : 'text-slate-500' }}">Jatuh tempo: {{ $task->due_date->translatedFormat('d M Y') }}</p>
                            @endif

                            <div class="mt-3 flex items-center gap-2 border-t border-slate-100 pt-3">
                                @if ($task->isEditableBy($user) && $meta)
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
                    @endforeach
                </div>
            </div>
        @empty
            <x-empty-state title="Belum ada tugas." class="border-none bg-transparent" />
        @endforelse
    </div>

    <script>
        (function () {
            const openButton = document.getElementById('open-create-task');
            const backdrop = document.getElementById('create-task-backdrop');
            const modal = document.getElementById('create-task-modal');

            if (!openButton || !backdrop || !modal) {
                return;
            }

            function openModal() {
                backdrop.classList.remove('hidden');
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                backdrop.classList.add('hidden');
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            openButton.addEventListener('click', openModal);
            document.querySelectorAll('[data-create-task-close]').forEach(function (el) {
                el.addEventListener('click', closeModal);
            });
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();

        (function () {
            const priorityFilter = document.getElementById('filter-priority');
            const assigneeFilter = document.getElementById('filter-assignee');
            const resetButton = document.getElementById('filter-reset');

            if (!priorityFilter || !assigneeFilter) {
                return;
            }

            const cards = Array.from(document.querySelectorAll('.task-card'));
            const sections = Array.from(document.querySelectorAll('[data-bucket-section]'));

            function applyFilters() {
                const priority = priorityFilter.value;
                const assignee = assigneeFilter.value;

                cards.forEach(function (card) {
                    const matchesPriority = priority === '' || card.dataset.priority === priority;
                    const assigneeIds = (card.dataset.assigneeIds || '').split(',');
                    const matchesAssignee = assignee === '' || assigneeIds.includes(assignee);
                    card.hidden = !(matchesPriority && matchesAssignee);
                });

                sections.forEach(function (section) {
                    section.hidden = section.querySelectorAll('.task-card:not([hidden])').length === 0;
                });
            }

            priorityFilter.addEventListener('change', applyFilters);
            assigneeFilter.addEventListener('change', applyFilters);
            resetButton?.addEventListener('click', function () {
                priorityFilter.value = '';
                assigneeFilter.value = '';
                applyFilters();
            });
        })();
    </script>
@endsection
