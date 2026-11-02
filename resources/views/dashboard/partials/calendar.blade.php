@php
    $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $levelColors = ['bg-amber-400', 'bg-sky-400', 'bg-emerald-400', 'bg-violet-400', 'bg-rose-400'];
    $levelColorFor = fn ($levelId) => $levelColors[$levelId % count($levelColors)];
@endphp

<x-card class="mt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-slate-950">Kalender KBM — {{ $calendarMonth->translatedFormat('F Y') }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                @if ($canManageDefault)
                    Jadwal KBM default per jenjang. <a href="{{ route('default-schedules.index') }}" class="font-semibold text-brand-700 hover:text-brand-900">Atur pola jadwal default</a>.
                @else
                    Referensi jadwal KBM default
                    @if ($canCreateOwnSessions) dan jadwal KBM kelompok Anda. @endif
                @endif
                Klik tanggal untuk melihat detail.
            </p>
        </div>
        <div class="flex items-center gap-2 text-sm font-semibold">
            <a href="{{ route('dashboard', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">&laquo;</a>
            <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">Hari Ini</a>
            <a href="{{ route('dashboard', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">&raquo;</a>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200">
        @foreach ($dayLabels as $label)
            <div class="bg-slate-50 px-2 py-2 text-center text-xs font-semibold text-slate-500">{{ $label }}</div>
        @endforeach

        @foreach ($calendarDays as $day)
            @php
                $isCurrentMonth = $day->month === $calendarMonth->month;
                $isSelected = $day->isSameDay($selectedDate);
                $matchingPatterns = $isCurrentMonth ? $defaultSchedules->filter(fn ($pattern) => $pattern->appliesTo($day)) : collect();
                $daySessionCount = $isCurrentMonth ? $ownSessions->get($day->toDateString(), collect())->count() : 0;
                $cellClasses = 'flex min-h-[4.5rem] flex-col items-center gap-1 bg-white py-2 sm:min-h-[5.5rem]'
                    .(! $isCurrentMonth ? ' bg-slate-50' : '')
                    .($isSelected ? ' ring-2 ring-inset ring-brand-600' : '');
            @endphp

            @if ($isCurrentMonth)
                <a href="{{ route('dashboard', ['month' => $calendarMonth->format('Y-m'), 'date' => $day->toDateString()]) }}" class="{{ $cellClasses }} transition hover:bg-amber-50">
            @else
                <div class="{{ $cellClasses }}">
            @endif
                <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $day->isToday() ? 'bg-brand-950 text-white' : ($isCurrentMonth ? 'text-slate-900' : 'text-slate-400') }}">
                    {{ $day->day }}
                </span>
                <div class="flex items-center gap-1">
                    @foreach ($matchingPatterns as $pattern)
                        <span class="h-1.5 w-1.5 rounded-full {{ $levelColorFor($pattern->level_id) }}" title="{{ $pattern->level->name }}"></span>
                    @endforeach
                    @if ($daySessionCount > 0)
                        <span class="rounded-full bg-brand-100 px-1.5 text-[0.65rem] font-semibold text-brand-700">{{ $daySessionCount }}</span>
                    @endif
                </div>
            @if ($isCurrentMonth)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 p-4">
        <h3 class="font-semibold text-slate-950">{{ $selectedDate->translatedFormat('l, d F Y') }}</h3>

        @if ($canManageDefault)
            <div class="mt-3 space-y-2">
                @forelse ($selectedPatterns as $pattern)
                    @php($exception = $pattern->exceptionOn($selectedDate))
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $levelColorFor($pattern->level_id) }}"></span>
                            {{ $pattern->level->name }}
                            @if ($pattern->start_time) <span class="text-slate-500">· {{ substr($pattern->start_time, 0, 5) }}–{{ substr($pattern->end_time, 0, 5) }}</span> @endif
                            @if ($exception) <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-600">Libur</span> @endif
                        </span>
                        @if ($exception)
                            <form method="POST" action="{{ route('default-schedules.exceptions.destroy', $exception) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Batalkan libur</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('default-schedules.exceptions.store', $pattern) }}">
                                @csrf
                                <input type="hidden" name="exception_date" value="{{ $selectedDate->toDateString() }}">
                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Liburkan tanggal ini</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada jadwal KBM default pada tanggal ini.</p>
                @endforelse
            </div>
        @endif

        @if ($canCreateOwnSessions)
            <div class="mt-4 {{ $canManageDefault ? 'border-t border-slate-100 pt-4' : '' }}">
                <h4 class="text-sm font-semibold text-slate-700">Jadwal KBM Kelompok</h4>
                <div class="mt-2 space-y-2">
                    @forelse ($selectedOwnSessions as $session)
                        <div class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            {{ $session->start_time ? substr($session->start_time, 0, 5).'–'.substr($session->end_time ?? '', 0, 5) : 'Jam belum diisi' }}
                            · {{ $session->classGrade?->name ?? 'Semua kelas' }}
                            <span class="ml-1 text-xs text-slate-400">({{ $session->status }})</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada jadwal KBM kelompok di tanggal ini.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('learning-sessions.store-own') }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="group_id" value="{{ $myGroupId }}">
                    <input type="hidden" name="session_date" value="{{ $selectedDate->toDateString() }}">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Kelas KBM</label>
                        <select name="class_grade_id" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                            <option value="">-- Semua kelas --</option>
                            @foreach ($calendarClassGrades as $classGrade)
                                <option value="{{ $classGrade->id }}">{{ $classGrade->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Jam Mulai</label>
                        <input type="time" name="start_time" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Jam Selesai</label>
                        <input type="time" name="end_time" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                    </div>
                    <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">+ Tambah Jadwal</button>
                </form>
            </div>
        @endif
    </div>
</x-card>
