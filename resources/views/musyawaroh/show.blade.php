@extends('layouts.app')

@section('title', $musyawaroh->title ?: 'Musyawaroh')
@section('page-title', 'Musyawaroh')
@section('breadcrumb', $musyawaroh->title ?: 'Detail Musyawaroh')

@section('content')
    @php $user = auth()->user(); @endphp

    <div class="mb-6">
        <p class="text-sm font-semibold text-amber-600">Pengurus</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ $musyawaroh->title ?: 'Musyawaroh' }}</h2>
        <p class="mt-2 text-sm text-slate-500">
            {{ $musyawaroh->meeting_date->translatedFormat('l, d M Y') }}
            @if ($musyawaroh->start_time)
                &middot; {{ \Illuminate\Support\Carbon::parse($musyawaroh->start_time)->format('H:i') }}@if ($musyawaroh->end_time)-{{ \Illuminate\Support\Carbon::parse($musyawaroh->end_time)->format('H:i') }}@endif WIB
            @endif
            @if ($musyawaroh->location)
                &middot; {{ $musyawaroh->location }}
            @endif
            &middot; dibuat oleh {{ $musyawaroh->createdBy?->name ?? '-' }}
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-1">
            <h3 class="mb-4 text-sm font-semibold text-slate-700">Daftar Hadir ({{ $musyawaroh->attendees->count() }})</h3>
            @if ($musyawaroh->attendees->isEmpty())
                <p class="text-sm text-slate-400">Belum ada data kehadiran.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($musyawaroh->attendees as $attendee)
                        <li class="flex items-center gap-2 text-sm text-slate-700">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $attendee->user_id ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            {{ $attendee->displayName() }}
                            @if (! $attendee->user_id)
                                <span class="text-xs text-slate-400">(tamu)</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <div class="space-y-4 lg:col-span-2">
            <x-card>
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Tambah Notulen</h3>
                <form method="POST" action="{{ route('notulen.store') }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
                    @csrf
                    <input type="hidden" name="musyawaroh_id" value="{{ $musyawaroh->id }}">
                    <input type="hidden" name="meeting_date" value="{{ $musyawaroh->meeting_date->format('Y-m-d') }}">
                    <input type="hidden" name="bucket" value="{{ $musyawaroh->title }}">
                    <input type="text" name="title" required placeholder="Poin notulen, misal: Persiapan KBM semester baru" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Tambah</button>
                </form>
            </x-card>

            <x-card>
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Notulen</h3>
                @if ($musyawaroh->notulens->isEmpty())
                    <x-empty-state title="Belum ada notulen." class="border-none bg-transparent" />
                @else
                    <div class="space-y-3">
                        @foreach ($musyawaroh->notulens as $notulen)
                            <div class="rounded-lg border border-slate-200 p-3">
                                <div class="flex items-start gap-3">
                                    <form method="POST" action="{{ route('notulen.toggle', $notulen) }}" data-toggle-notulen-form>
                                        @csrf
                                        @method('PATCH')
                                        <input type="checkbox" onchange="this.form.requestSubmit()" @checked($notulen->is_done) class="mt-1 h-4 w-4 rounded border-slate-300">
                                    </form>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium {{ $notulen->is_done ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $notulen->title }}</p>
                                        @if ($notulen->content && $notulen->content !== $notulen->title)
                                            <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $notulen->content }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-slate-400">
                                            oleh {{ $notulen->createdBy?->name ?? '-' }}
                                            @if ($notulen->is_done && $notulen->checkedBy)
                                                &middot; ditandai selesai oleh {{ $notulen->checkedBy->name }}
                                            @endif
                                        </p>

                                        @if ($notulen->task)
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                                Tugas: {{ $notulen->task->title }} ({{ $notulen->task->assignees->pluck('name')->join(', ') }})
                                            </span>
                                        @else
                                            <button type="button" data-open-task-modal="task-modal-{{ $notulen->id }}" class="mt-2 text-xs font-semibold text-brand-600 hover:text-brand-700">
                                                + Jadikan Tugas
                                            </button>
                                        @endif
                                    </div>

                                    @if ($notulen->isEditableBy($user))
                                        <form method="POST" action="{{ route('notulen.destroy', $notulen) }}" onsubmit="return confirm('Hapus notulen ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-500 hover:text-rose-700">Hapus</button>
                                        </form>
                                    @endif
                                </div>

                                @if (! $notulen->task)
                                    <div id="task-modal-{{ $notulen->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true">
                                        <div class="w-full max-w-lg">
                                            <x-card class="relative">
                                                <button type="button" data-close-task-modal class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup">&times;</button>
                                                <h4 class="mb-4 text-lg font-semibold">Jadikan Tugas</h4>
                                                <form method="POST" action="{{ route('tasks.store') }}" class="space-y-3">
                                                    @csrf
                                                    <input type="hidden" name="notulen_id" value="{{ $notulen->id }}">
                                                    <input type="hidden" name="bucket" value="{{ $musyawaroh->title }}">
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium">Judul Tugas</label>
                                                        <input type="text" name="title" required value="{{ $notulen->title }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium">Jatuh Tempo</label>
                                                        <input type="date" name="due_date" value="{{ now()->addDays(\App\Models\Task::DEFAULT_DUE_DATE_DAYS)->format('Y-m-d') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium">Prioritas</label>
                                                        <select name="priority" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                                            <option value="low">Rendah</option>
                                                            <option value="medium" selected>Sedang</option>
                                                            <option value="high">Tinggi</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <span class="mb-1 block text-sm font-medium">Ditugaskan Kepada</span>
                                                        @include('tasks.partials.assignee-picker', ['recipients' => $recipients, 'selectedIds' => []])
                                                    </div>
                                                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan Tugas</button>
                                                </form>
                                            </x-card>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-open-task-modal]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById(button.dataset.openTaskModal)?.classList.remove('hidden');
                    document.getElementById(button.dataset.openTaskModal)?.classList.add('flex');
                });
            });

            document.querySelectorAll('[data-close-task-modal]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const modal = button.closest('[role="dialog"]');
                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                });
            });
        });
    </script>
@endsection
