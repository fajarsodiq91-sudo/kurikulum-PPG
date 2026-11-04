@php
    $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $levelDotColors = ['bg-amber-500', 'bg-sky-600', 'bg-emerald-600', 'bg-violet-600', 'bg-rose-600', 'bg-orange-500', 'bg-teal-600'];
    $levelDotFor = fn ($levelId) => $levelDotColors[$levelId % count($levelDotColors)];
    $headerClassFor = fn ($i) => $i >= 5 ? 'bg-rose-600 text-white' : 'bg-slate-800 text-white';
    $dateClassFor = fn ($dayOfWeek) => in_array($dayOfWeek, [0, 6], true) ? 'text-rose-600' : 'text-slate-900';
@endphp

<x-card class="mt-6">
    <p class="text-sm font-semibold text-amber-600">Kalender KBM</p>
    <div class="mt-1 flex flex-wrap items-center gap-3">
        <a href="{{ route('dashboard') }}" class="rounded-full border border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Hari Ini</a>
        <div class="flex items-center">
            <a href="{{ route('dashboard', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}" class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Bulan sebelumnya">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <a href="{{ route('dashboard', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}" class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Bulan berikutnya">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <h2 class="text-xl font-semibold text-slate-950">{{ $calendarMonth->translatedFormat('F Y') }}</h2>
    </div>
    <p class="mt-2 text-sm text-slate-500">
        @if ($canManageDefault)
            Jadwal KBM default per jenjang. <a href="{{ route('default-schedules.index') }}" class="font-semibold text-brand-700 hover:text-brand-900">Atur pola jadwal default</a>.
        @else
            Referensi jadwal KBM default
            @if ($canCreateOwnSessions) dan jadwal KBM kelompok Anda. @endif
        @endif
        Klik tanggal untuk melihat detail.
    </p>

    <div class="mt-5 grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200">
        @foreach ($dayLabels as $i => $label)
            <div class="px-2 py-1.5 text-center text-xs font-semibold {{ $headerClassFor($i) }}">{{ $label }}</div>
        @endforeach

        @foreach ($calendarDays as $day)
            @php
                $i = $day->dayOfWeek;
                $isCurrentMonth = $day->month === $calendarMonth->month;
                $isSelected = $day->isSameDay($selectedDate);
                $matchingPatterns = $isCurrentMonth ? $defaultSchedules->filter(fn ($pattern) => $pattern->appliesTo($day)) : collect();
                $daySessions = $isCurrentMonth ? $ownSessions->get($day->toDateString(), collect()) : collect();
                $hasHoliday = $matchingPatterns->contains(fn ($pattern) => $pattern->exceptionOn($day) !== null);
                $dayBadges = $matchingPatterns->unique('level_id')
                    ->map(function ($pattern) use ($day, $levelDotFor) {
                        $exception = $pattern->exceptionOn($day);
                        $time = $pattern->start_time ? substr($pattern->start_time, 0, 5).'–'.substr($pattern->end_time, 0, 5) : null;

                        return [
                            'bg' => $levelDotFor($pattern->level_id),
                            'code' => $pattern->level->code,
                            'title' => $pattern->level->name.($exception ? ' · Libur' : ($time ? ' · '.$time : '')),
                            'holiday' => $exception !== null,
                        ];
                    })
                    ->concat($daySessions->isNotEmpty() ? [[
                        'bg' => 'bg-brand-600',
                        'code' => null,
                        'title' => 'Jadwal KBM Kelompok'.($daySessions->first()->start_time ? ' · '.substr($daySessions->first()->start_time, 0, 5) : ''),
                        'holiday' => false,
                    ]] : []);
                $maxVisibleBadges = 4;
                $visibleBadges = $dayBadges->take($maxVisibleBadges);
                $hiddenBadgeCount = max(0, $dayBadges->count() - $maxVisibleBadges);
                $cellClasses = 'relative flex min-h-[3.5rem] flex-col items-start gap-0.5 bg-white p-1.5 sm:min-h-[4.25rem]'
                    .(! $isCurrentMonth ? ' bg-slate-50' : '')
                    .($day->isToday() ? ' bg-brand-100 ring-1 ring-inset ring-brand-300' : '')
                    .($isSelected && ! $day->isToday() ? ' ring-2 ring-inset ring-brand-600' : '');
            @endphp

            @if ($isCurrentMonth)
                <a href="{{ route('dashboard', ['month' => $calendarMonth->format('Y-m'), 'date' => $day->toDateString()]) }}" class="{{ $cellClasses }} transition hover:bg-slate-50">
            @else
                <div class="{{ $cellClasses }}">
            @endif
                @if ($hasHoliday)
                    <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-rose-600" title="Ada jadwal libur"></span>
                @endif
                <span class="text-sm {{ $day->isToday() ? 'font-bold text-brand-700' : ($isCurrentMonth ? 'font-medium '.$dateClassFor($i) : 'font-medium text-slate-300') }}">
                    {{ $day->day }}
                </span>
                @if ($dayBadges->isNotEmpty())
                    <span class="flex flex-wrap items-center gap-1">
                        @foreach ($visibleBadges as $badge)
                            <span class="flex h-4 w-4 items-center justify-center rounded-full ring-1 ring-white {{ $badge['bg'] }} {{ $badge['holiday'] ? 'opacity-40' : '' }}" title="{{ $badge['title'] }}">
                                @if ($badge['code'])
                                    <x-level-icon :code="$badge['code']" class="h-2.5 w-2.5 text-white" />
                                @else
                                    <x-sidebar-icon name="users" class="h-2.5 w-2.5 text-white" />
                                @endif
                            </span>
                        @endforeach
                        @if ($hiddenBadgeCount > 0)
                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-slate-300 text-[0.55rem] font-bold text-slate-700" title="{{ $hiddenBadgeCount }} jadwal lainnya">
                                +{{ $hiddenBadgeCount }}
                            </span>
                        @endif
                    </span>
                @endif
            @if ($isCurrentMonth)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>

    @php
        $legendLevels = $defaultSchedules->pluck('level')->filter()->unique('id')->sortBy('sort_order');
    @endphp
    @if ($legendLevels->isNotEmpty() || $canCreateOwnSessions)
        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-700">
            @foreach ($legendLevels as $level)
                <span class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $levelDotFor($level->id) }}">
                        <x-level-icon :code="$level->code" class="h-3.5 w-3.5 text-white" />
                    </span>
                    {{ $level->name }}
                </span>
            @endforeach
            @if ($canCreateOwnSessions)
                <span class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-600">
                        <x-sidebar-icon name="users" class="h-3.5 w-3.5 text-white" />
                    </span>
                    KBM Kelompok
                </span>
            @endif
        </div>
    @endif

    <div class="mt-6 rounded-xl border border-slate-200 p-4">
        <h3 class="font-semibold text-slate-950">{{ $selectedDate->translatedFormat('l, d F Y') }}</h3>

        @if ($canManageDefault)
            <div class="mt-3 space-y-2">
                @forelse ($selectedPatterns as $pattern)
                    @php($exception = $pattern->exceptionOn($selectedDate))
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <span class="flex items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $levelDotFor($pattern->level_id) }}">
                                <x-level-icon :code="$pattern->level->code" class="h-4 w-4 text-white" />
                            </span>
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
