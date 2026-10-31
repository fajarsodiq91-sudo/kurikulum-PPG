@extends('layouts.app')

@section('title', 'Musyawaroh')
@section('page-title', 'Musyawaroh')
@section('breadcrumb', 'Musyawaroh')

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
        $preselectedAssigneeId = request('assign_to');
        $notulenFormShouldOpen = $errors->has('content');
        $formShouldOpen = ($errors->any() && ! $notulenFormShouldOpen) || $preselectedAssigneeId !== null;
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-semibold text-amber-600">Musyawaroh</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Papan Tugas &amp; Notulen</h2>
            <p class="mt-2 text-sm text-slate-500">Catat dan pantau tugas atau action plan, serta notulen hasil musyawarah/rapat pengurus, sesuai hirarki kepengurusan.</p>
        </div>
        @if (\App\Support\Access::can('manage-tasks'))
            <div class="flex shrink-0 gap-2">
                <button type="button" id="open-create-notulen" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    + Tambah Notulen
                </button>
                <button type="button" id="open-create-task" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                    + Tambah Tugas
                </button>
            </div>
        @endif
    </div>

    <div class="mb-6 inline-flex items-center gap-1 rounded-lg border border-slate-300 p-0.5 text-sm" role="group" aria-label="Tampilan Musyawaroh" data-musyawaroh-initial-view="{{ $notulenFormShouldOpen ? 'notulen' : 'tasks' }}">
        <button type="button" id="musyawaroh-view-tasks-btn" data-musyawaroh-view="tasks" class="rounded-md px-3 py-1.5 font-medium transition">Tugas</button>
        <button type="button" id="musyawaroh-view-notulen-btn" data-musyawaroh-view="notulen" class="rounded-md px-3 py-1.5 font-medium transition">Notulen</button>
    </div>

    @if (\App\Support\Access::can('manage-tasks'))
        <div id="create-task-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $formShouldOpen ? '' : 'hidden' }}" data-create-task-close></div>

        <div id="create-task-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $formShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-task-title">
            <div class="mx-auto my-6 w-full max-w-2xl">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-task-close>&times;</button>
                    <h2 id="create-task-title" class="mb-4 text-xl font-semibold">Tambah Tugas</h2>
                    <form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" for="title">Judul Tugas</label>
                            <input id="title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('title') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" for="description">Deskripsi / Catatan Musyawaroh</label>
                            <textarea id="description" name="description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('description') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" for="bucket">Kategori / Sumber (misal nama musyawaroh)</label>
                            <input id="bucket" name="bucket" type="text" list="bucket-suggestions" placeholder="Hasil Musyawaroh Pengurus Oktober 2026" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('bucket') }}">
                            <datalist id="bucket-suggestions">
                                @foreach ($bucketSuggestions as $suggestion)
                                    <option value="{{ $suggestion }}"></option>
                                @endforeach
                            </datalist>
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
                            <input id="due_date" name="due_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('due_date', now()->addDays(\App\Models\Task::DEFAULT_DUE_DATE_DAYS)->format('Y-m-d')) }}">
                            <p class="mt-1 text-xs text-slate-500">Default H+{{ \App\Models\Task::DEFAULT_DUE_DATE_DAYS }} dari hari ini, bisa diubah.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" for="link">Link (opsional)</label>
                            <input id="link" name="link" type="url" placeholder="https://drive.google.com/..." class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('link') }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" for="attachment">Lampiran File (opsional)</label>
                            <input id="attachment" name="attachment" type="file" class="w-full rounded-lg border border-slate-300 px-3 py-2 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium">
                            <p class="mt-1 text-xs text-slate-500">PDF, Word, Excel, PowerPoint, atau gambar. Maks. 10 MB.</p>
                        </div>
                        <div class="md:col-span-2">
                            <span class="mb-2 block text-sm font-medium text-slate-700">Ditugaskan Kepada</span>
                            @include('tasks.partials.assignee-picker', ['recipients' => $recipients, 'selectedIds' => old('assignees', $preselectedAssigneeId !== null ? [$preselectedAssigneeId] : [])])
                            <p class="mt-1 text-xs text-slate-500">Hanya pengurus di jalur vertikal Anda (atasan dan cabang Anda sendiri) yang bisa dipilih.</p>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan Tugas</button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>

        <div id="create-notulen-backdrop" class="fixed inset-0 z-40 bg-slate-900/50 {{ $notulenFormShouldOpen ? '' : 'hidden' }}" data-create-notulen-close></div>

        <div id="create-notulen-modal" class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 {{ $notulenFormShouldOpen ? '' : 'hidden' }}" role="dialog" aria-modal="true" aria-labelledby="create-notulen-title">
            <div class="mx-auto my-6 w-full max-w-2xl">
                <x-card class="relative">
                    <button type="button" class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup" data-create-notulen-close>&times;</button>
                    <h2 id="create-notulen-title" class="mb-4 text-xl font-semibold">Tambah Notulen</h2>
                    <p class="mb-4 text-sm text-slate-500">Catat hasil musyawaroh yang tidak perlu ditindaklanjuti sebagai tugas.</p>
                    <form method="POST" action="{{ route('notulen.store') }}" class="grid gap-4 md:grid-cols-2">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" for="notulen-title">Judul</label>
                            <input id="notulen-title" name="title" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('title') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" for="notulen-bucket">Kategori / Sumber (misal nama musyawaroh)</label>
                            <input id="notulen-bucket" name="bucket" type="text" list="bucket-suggestions" placeholder="Hasil Musyawaroh Pengurus Oktober 2026" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('bucket') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2" for="notulen-meeting-date">Tanggal Musyawaroh</label>
                            <input id="notulen-meeting-date" name="meeting_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2" value="{{ old('meeting_date', now()->format('Y-m-d')) }}">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2" for="notulen-content">Isi Notulen</label>
                            <textarea id="notulen-content" name="content" rows="5" required class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('content') }}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan Notulen</button>
                        </div>
                    </form>
                </x-card>
            </div>
        </div>
    @endif

    <div id="musyawaroh-view-notulen" class="{{ $notulenFormShouldOpen ? '' : 'hidden' }}">
        @if ($notulens->isEmpty())
            <x-empty-state title="Belum ada notulen." class="mb-6 border-none bg-transparent" />
        @else
            <x-card class="mb-6">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Notulen Musyawaroh</h3>
                <div class="space-y-4">
                    @foreach ($notulens as $notulen)
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="font-medium text-slate-900">{{ $notulen->title }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ $notulen->bucket ?: 'Tanpa Kategori' }}
                                        @if ($notulen->meeting_date)
                                            &middot; {{ $notulen->meeting_date->translatedFormat('d M Y') }}
                                        @endif
                                        &middot; oleh {{ $notulen->createdBy?->name ?? '-' }}
                                    </p>
                                </div>
                                @if ($notulen->isEditableBy($user))
                                    <form method="POST" action="{{ route('notulen.destroy', $notulen) }}" onsubmit="return confirm('Hapus notulen ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-rose-600 hover:underline">Hapus</button>
                                    </form>
                                @endif
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $notulen->content }}</p>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    </div>

    <div id="musyawaroh-view-tasks" class="{{ $notulenFormShouldOpen ? 'hidden' : '' }}">
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
            <div class="flex items-center gap-1 rounded-lg border border-slate-300 p-0.5 text-sm" role="group" aria-label="Mode tampilan">
                <button type="button" id="view-mode-bucket" data-view-mode="bucket" class="rounded-md px-3 py-1 font-medium transition">Bucket</button>
                <button type="button" id="view-mode-list" data-view-mode="list" class="rounded-md px-3 py-1 font-medium transition">Daftar</button>
            </div>
        </x-card>
    @endif

    <div id="tasks-view-bucket" class="space-y-8">
        @forelse ($tasksByBucket as $bucket => $bucketTasks)
            <div data-bucket-section>
                <h3 class="mb-3 flex items-center justify-between text-sm font-semibold text-slate-700">
                    <span>{{ $bucket }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ $bucketTasks->count() }}</span>
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($bucketTasks as $task)
                        @include('tasks.partials.task-card', ['task' => $task, 'statusMeta' => $statusMeta, 'priorityMeta' => $priorityMeta, 'user' => $user])
                    @endforeach
                </div>
            </div>
        @empty
            <x-empty-state title="Belum ada tugas." class="border-none bg-transparent" />
        @endforelse
    </div>

    <div id="tasks-view-list" class="hidden">
        @if ($tasks->isEmpty())
            <x-empty-state title="Belum ada tugas." class="border-none bg-transparent" />
        @else
            <x-card class="overflow-x-auto p-0" data-bucket-section>
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Judul</th>
                            <th class="px-4 py-3 font-semibold">Kategori</th>
                            <th class="px-4 py-3 font-semibold">Prioritas</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Untuk</th>
                            <th class="px-4 py-3 font-semibold">Dari</th>
                            <th class="px-4 py-3 font-semibold">Jatuh Tempo</th>
                            <th class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            @php
                                $meta = $statusMeta[$task->status] ?? null;
                                $isOverdue = $task->due_date && $task->status !== 'done' && $task->due_date->isPast();
                            @endphp
                            <tr
                                class="task-card border-b border-slate-100 last:border-0"
                                data-priority="{{ $task->priority }}"
                                data-assignee-ids="{{ $task->assignees->pluck('id')->implode(',') }}"
                            >
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ $task->title }}</p>
                                    @if ($task->description)
                                        <p class="mt-0.5 max-w-xs truncate text-xs text-slate-500">{{ $task->description }}</p>
                                    @endif
                                    @if ($task->link || $task->attachment)
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            @if ($task->link)
                                                <a href="{{ $task->link }}" target="_blank" rel="noopener" class="text-xs font-medium text-brand-600 hover:underline">🔗 Link</a>
                                            @endif
                                            @if ($task->attachment)
                                                <a href="{{ route('tasks.attachment', $task) }}" target="_blank" class="text-xs font-medium text-brand-600 hover:underline">📎 Lampiran</a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $task->bucket ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $priorityMeta[$task->priority]['class'] ?? 'bg-slate-100 text-slate-600' }}">{{ $priorityMeta[$task->priority]['label'] ?? $task->priority }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if ($meta)
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                        @endif
                                        @if ($isOverdue)
                                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-medium text-rose-700">Terlambat</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $task->assignees->pluck('name')->join(', ') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $task->assignedBy?->name ?? '-' }}</td>
                                <td class="px-4 py-3 {{ $isOverdue ? 'font-medium text-rose-600' : 'text-slate-600' }}">{{ $task->due_date?->translatedFormat('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
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
                                            <a href="{{ route('tasks.edit', $task) }}" class="rounded-lg px-2 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-900">Ubah</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-card>
        @endif
    </div>
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
            const openButton = document.getElementById('open-create-notulen');
            const backdrop = document.getElementById('create-notulen-backdrop');
            const modal = document.getElementById('create-notulen-modal');

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
            document.querySelectorAll('[data-create-notulen-close]').forEach(function (el) {
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
            const tasksButton = document.getElementById('musyawaroh-view-tasks-btn');
            const notulenButton = document.getElementById('musyawaroh-view-notulen-btn');
            const tasksView = document.getElementById('musyawaroh-view-tasks');
            const notulenView = document.getElementById('musyawaroh-view-notulen');
            const toggleGroup = tasksButton?.closest('[data-musyawaroh-initial-view]');

            if (!tasksButton || !notulenButton || !tasksView || !notulenView) {
                return;
            }

            const storageKey = 'musyawaroh-view-mode';

            function setMode(mode) {
                const isNotulen = mode === 'notulen';
                tasksView.classList.toggle('hidden', isNotulen);
                notulenView.classList.toggle('hidden', !isNotulen);
                tasksButton.classList.toggle('bg-white', !isNotulen);
                tasksButton.classList.toggle('shadow-sm', !isNotulen);
                tasksButton.classList.toggle('text-slate-900', !isNotulen);
                tasksButton.classList.toggle('text-slate-500', isNotulen);
                notulenButton.classList.toggle('bg-white', isNotulen);
                notulenButton.classList.toggle('shadow-sm', isNotulen);
                notulenButton.classList.toggle('text-slate-900', isNotulen);
                notulenButton.classList.toggle('text-slate-500', !isNotulen);

                try {
                    localStorage.setItem(storageKey, mode);
                } catch (error) {
                    // Private browsing or blocked storage - the toggle still works for this page view.
                }
            }

            tasksButton.addEventListener('click', () => setMode('tasks'));
            notulenButton.addEventListener('click', () => setMode('notulen'));

            // A validation error redirect from the notulen form must land back on the
            // notulen tab, overriding whatever tab the user had saved previously.
            const forcedInitialView = toggleGroup?.dataset.musyawarohInitialView;

            if (forcedInitialView === 'notulen') {
                setMode('notulen');
            } else {
                let savedMode = 'tasks';
                try {
                    savedMode = localStorage.getItem(storageKey) || 'tasks';
                } catch (error) {
                    savedMode = 'tasks';
                }

                setMode(savedMode === 'notulen' ? 'notulen' : 'tasks');
            }
        })();

        (function () {
            const bucketButton = document.getElementById('view-mode-bucket');
            const listButton = document.getElementById('view-mode-list');
            const bucketView = document.getElementById('tasks-view-bucket');
            const listView = document.getElementById('tasks-view-list');

            if (!bucketButton || !listButton || !bucketView || !listView) {
                return;
            }

            const storageKey = 'tasks-view-mode';
            const activeClasses = ['bg-white', 'shadow-sm', 'text-slate-900'];
            const inactiveClasses = ['text-slate-500'];

            function setMode(mode) {
                const isList = mode === 'list';
                bucketView.classList.toggle('hidden', isList);
                listView.classList.toggle('hidden', !isList);
                bucketButton.classList.toggle('bg-white', !isList);
                bucketButton.classList.toggle('shadow-sm', !isList);
                bucketButton.classList.toggle('text-slate-900', !isList);
                bucketButton.classList.toggle('text-slate-500', isList);
                listButton.classList.toggle('bg-white', isList);
                listButton.classList.toggle('shadow-sm', isList);
                listButton.classList.toggle('text-slate-900', isList);
                listButton.classList.toggle('text-slate-500', !isList);

                try {
                    localStorage.setItem(storageKey, mode);
                } catch (error) {
                    // Private browsing or blocked storage - the toggle still works for this page view.
                }
            }

            bucketButton.addEventListener('click', () => setMode('bucket'));
            listButton.addEventListener('click', () => setMode('list'));

            let savedMode = 'bucket';
            try {
                savedMode = localStorage.getItem(storageKey) || 'bucket';
            } catch (error) {
                savedMode = 'bucket';
            }

            setMode(savedMode === 'list' ? 'list' : 'bucket');
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
