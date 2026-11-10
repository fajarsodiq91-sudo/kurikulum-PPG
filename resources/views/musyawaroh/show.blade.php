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
            @php
                $statusBadgeClass = [
                    'hadir' => 'bg-emerald-100 text-emerald-700',
                    'izin' => 'bg-amber-100 text-amber-700',
                    'alpha' => 'bg-rose-100 text-rose-700',
                    'diundang' => 'bg-slate-100 text-slate-500',
                ];
                $hadirCount = $musyawaroh->attendees->where('status', 'hadir')->count();
            @endphp
            <h3 class="mb-1 text-sm font-semibold text-slate-700">
                Daftar Hadir ({{ $hadirCount }}/{{ $musyawaroh->attendees->count() }})
            </h3>
            <p class="mb-4 text-xs text-slate-500">Tandai Hadir/Izin/Alpha untuk setiap peserta yang diundang.</p>
            @if ($musyawaroh->attendees->isEmpty())
                <p class="text-sm text-slate-400">Belum ada peserta yang diundang.</p>
            @else
                <form method="POST" action="{{ route('musyawaroh.attendance.update', $musyawaroh) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <ul class="space-y-2">
                        @foreach ($musyawaroh->attendees->sortBy(fn ($attendee) => $attendee->displayName()) as $attendee)
                            <li class="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-700">
                                <span class="min-w-0 truncate">
                                    {{ $attendee->displayName() }}
                                    @if (! $attendee->user_id)
                                        <span class="text-xs text-slate-400">(tamu)</span>
                                    @endif
                                </span>
                                <div class="inline-flex shrink-0 overflow-hidden rounded-md border border-slate-300 text-[11px] font-medium">
                                    <label class="cursor-pointer px-2 py-1 has-[:checked]:bg-emerald-100 has-[:checked]:text-emerald-700">
                                        <input type="radio" name="status[{{ $attendee->id }}]" value="hadir" class="sr-only" @checked($attendee->status === 'hadir')>
                                        Hadir
                                    </label>
                                    <label class="cursor-pointer border-l border-slate-300 px-2 py-1 has-[:checked]:bg-amber-100 has-[:checked]:text-amber-700">
                                        <input type="radio" name="status[{{ $attendee->id }}]" value="izin" class="sr-only" @checked($attendee->status === 'izin')>
                                        Izin
                                    </label>
                                    <label class="cursor-pointer border-l border-slate-300 px-2 py-1 has-[:checked]:bg-rose-100 has-[:checked]:text-rose-700">
                                        <input type="radio" name="status[{{ $attendee->id }}]" value="alpha" class="sr-only" @checked($attendee->status === 'alpha')>
                                        Alpha
                                    </label>
                                </div>
                                <button type="button" onclick="if (confirm('Hapus {{ addslashes($attendee->displayName()) }} dari daftar hadir?')) { document.getElementById('delete-attendee-{{ $attendee->id }}').submit(); }" class="shrink-0 text-slate-300 hover:text-rose-600" aria-label="Hapus dari daftar hadir">&times;</button>
                            </li>
                        @endforeach
                    </ul>
                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-700">Simpan Daftar Hadir</button>
                </form>

                @foreach ($musyawaroh->attendees as $attendee)
                    <form id="delete-attendee-{{ $attendee->id }}" method="POST" action="{{ route('musyawaroh.attendees.destroy', [$musyawaroh, $attendee]) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @endif

            <div class="mt-4 border-t border-slate-200 pt-4">
                <p class="mb-1 text-xs font-semibold text-slate-600">Tambah Peserta Lain</p>
                <p class="mb-2 text-xs text-slate-400">Pengurus lain yang hadir walau tidak diundang, atau orang di luar sistem - keduanya langsung tercatat Hadir.</p>

                @if ($availableMembers->isNotEmpty())
                    <form method="POST" action="{{ route('musyawaroh.attendees.store', $musyawaroh) }}" class="mb-2 flex gap-2">
                        @csrf
                        <select name="user_id" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs">
                            <option value="">Pilih pengurus...</option>
                            @foreach ($availableMembers as $member)
                                <option value="{{ $member->user_id }}">{{ $member->user_name }}@if ($member->jabatan) ({{ $member->jabatan }})@endif</option>
                            @endforeach
                        </select>
                        <button type="submit" class="shrink-0 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-700">+ Hadir</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('musyawaroh.attendees.store', $musyawaroh) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="manual_name" placeholder="Nama tamu / belum terdaftar" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs">
                    <button type="submit" class="shrink-0 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">+ Tambah</button>
                </form>
            </div>
        </x-card>

        <div class="space-y-4 lg:col-span-2">
            <x-card>
                <h3 class="mb-1 text-sm font-semibold text-slate-700">Tambah Notulen</h3>
                <p class="mb-4 text-xs text-slate-500">Tambahkan poin notulen satu per satu. Setelah terkumpul beberapa dan siap, kirim semuanya sekaligus sebagai 1 resume lewat tombol "Kirim Notulen" di bawah.</p>
                <form method="POST" action="{{ route('notulen.store') }}" class="grid gap-3 sm:grid-cols-[1fr_auto]">
                    @csrf
                    <input type="hidden" name="musyawaroh_id" value="{{ $musyawaroh->id }}">
                    <input type="hidden" name="meeting_date" value="{{ $musyawaroh->meeting_date->format('Y-m-d') }}">
                    <input type="hidden" name="bucket" value="{{ $musyawaroh->title }}">
                    <input type="text" name="title" required placeholder="Poin notulen, misal: Persiapan KBM semester baru" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan</button>
                </form>
            </x-card>

            <x-card>
                @php $draftCount = $musyawaroh->notulens->where('status', \App\Models\Notulen::STATUS_DRAFT)->count(); @endphp
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-700">Notulen</h3>
                    @if ($musyawaroh->isEditableBy($user) && $draftCount > 0)
                        <form method="POST" action="{{ route('musyawaroh.notulen.send', $musyawaroh) }}" onsubmit="return confirm('Kirim {{ $draftCount }} notulen draft sebagai 1 resume ke seluruh daftar hadir musyawaroh (termasuk yang tidak hadir)? Setelah dikirim, notulen tersebut tidak bisa diubah lagi.')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Kirim Notulen ({{ $draftCount }} draft)</button>
                        </form>
                    @endif
                </div>
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
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-medium {{ $notulen->is_done ? 'text-slate-400 line-through' : 'text-slate-900' }}">{{ $notulen->title }}</p>
                                            @if ($notulen->status === \App\Models\Notulen::STATUS_FINAL)
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-medium text-emerald-700">Terkirim</span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">Draft</span>
                                            @endif
                                        </div>
                                        @if ($notulen->content && $notulen->content !== $notulen->title)
                                            <p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $notulen->content }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-slate-400">
                                            oleh {{ $notulen->createdBy?->name ?? '-' }}
                                            @if ($notulen->is_done && $notulen->checkedBy)
                                                &middot; ditandai selesai oleh {{ $notulen->checkedBy->name }}
                                            @endif
                                            @if ($notulen->status === \App\Models\Notulen::STATUS_FINAL && $notulen->sent_at)
                                                &middot; dikirim {{ $notulen->sent_at->translatedFormat('d M Y H:i') }}
                                            @endif
                                        </p>

                                        <div class="mt-2 flex flex-wrap items-center gap-3">
                                            @if ($notulen->task)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                                    Tugas: {{ $notulen->task->title }} ({{ $notulen->task->assignees->pluck('name')->join(', ') }})
                                                </span>
                                            @else
                                                <button type="button" data-open-task-modal="task-modal-{{ $notulen->id }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                                                    + Jadikan Tugas
                                                </button>
                                            @endif

                                            @if ($notulen->isContentEditableBy($user))
                                                <button type="button" data-open-task-modal="edit-modal-{{ $notulen->id }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                                                    Edit
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($notulen->isEditableBy($user))
                                        <form method="POST" action="{{ route('notulen.destroy', $notulen) }}" onsubmit="return confirm('Hapus notulen ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-500 hover:text-rose-700">Hapus</button>
                                        </form>
                                    @endif
                                </div>

                                @if ($notulen->isContentEditableBy($user))
                                    <div id="edit-modal-{{ $notulen->id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true">
                                        <div class="w-full max-w-lg">
                                            <x-card class="relative">
                                                <button type="button" data-close-task-modal class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup">&times;</button>
                                                <h4 class="mb-4 text-lg font-semibold">Edit Notulen</h4>
                                                <form method="POST" action="{{ route('notulen.update', $notulen) }}" class="space-y-3">
                                                    @csrf
                                                    @method('PUT')
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium">Judul</label>
                                                        <input type="text" name="title" required value="{{ $notulen->title }}" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-sm font-medium">Catatan (opsional)</label>
                                                        <textarea name="content" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ $notulen->content !== $notulen->title ? $notulen->content : '' }}</textarea>
                                                    </div>
                                                    <button type="submit" class="w-full rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Simpan Perubahan</button>
                                                </form>
                                            </x-card>
                                        </div>
                                    </div>
                                @endif

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
