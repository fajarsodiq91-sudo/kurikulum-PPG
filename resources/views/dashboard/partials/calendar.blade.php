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
            <div class="px-2 py-2 text-center text-xs font-semibold {{ $headerClassFor($i) }}">{{ $label }}</div>
        @endforeach

        @foreach ($calendarDays as $day)
            @php
                $i = $day->dayOfWeek;
                $isCurrentMonth = $day->month === $calendarMonth->month;
                $isSelected = $day->isSameDay($selectedDate);
                $matchingPatterns = $isCurrentMonth ? $defaultSchedules->filter(fn ($pattern) => $pattern->appliesTo($day)) : collect();
                $daySessions = $isCurrentMonth ? $ownSessions->get($day->toDateString(), collect()) : collect();
                $dayDots = $matchingPatterns->pluck('level_id')->unique()->map($levelDotFor)
                    ->concat($daySessions->isNotEmpty() ? ['bg-brand-600'] : []);
                $cellClasses = 'flex min-h-[4.5rem] flex-col items-center gap-1 bg-white p-1.5 sm:min-h-[5.5rem]'
                    .(! $isCurrentMonth ? ' bg-slate-50' : '')
                    .($isSelected && ! $day->isToday() ? ' ring-2 ring-inset ring-brand-600' : '');
            @endphp

            @if ($isCurrentMonth)
                <a href="{{ route('dashboard', ['month' => $calendarMonth->format('Y-m'), 'date' => $day->toDateString()]) }}" class="{{ $cellClasses }} transition hover:bg-slate-50">
            @else
                <div class="{{ $cellClasses }}">
            @endif
                <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-medium {{ $day->isToday() ? 'bg-brand-600 font-semibold text-white' : ($isCurrentMonth ? $dateClassFor($i) : 'text-slate-300') }}">
                    {{ $day->day }}
                </span>
                @if ($dayDots->isNotEmpty())
                    <span class="flex flex-wrap items-center justify-center gap-1">
                        @foreach ($dayDots as $dot)
                            <span class="h-2.5 w-2.5 rounded-full ring-1 ring-white {{ $dot }}"></span>
                        @endforeach
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
        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-slate-700">
            @foreach ($legendLevels as $level)
                <span class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full {{ $levelDotFor($level->id) }}"></span>
                    {{ $level->name }}
                </span>
            @endforeach
            @if ($canCreateOwnSessions)
                <span class="flex items-center gap-2">
                    <span class="h-3 w-3 rounded-full bg-brand-600"></span>
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
                            <span class="h-2 w-2 rounded-full {{ $levelDotFor($pattern->level_id) }}"></span>
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
