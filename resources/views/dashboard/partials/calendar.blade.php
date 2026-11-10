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
            <a href="{{ route('dashboard', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m'), 'group_id' => $calendarGroupId]) }}" class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Bulan sebelumnya">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <a href="{{ route('dashboard', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m'), 'group_id' => $calendarGroupId]) }}" class="rounded-full p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Bulan berikutnya">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <h2 class="text-xl font-semibold text-slate-950">{{ $calendarMonth->translatedFormat('F Y') }}</h2>
    </div>

    @if ($calendarGroups->count() > 1)
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                <input type="hidden" name="month" value="{{ $calendarMonth->format('Y-m') }}">
                <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                <label class="text-sm font-medium text-slate-600" for="calendar-group-select">Kelompok</label>
                <select id="calendar-group-select" name="group_id" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                    <option value="">-- Umum (Daerah) --</option>
                    @foreach ($calendarGroups as $group)
                        <option value="{{ $group->id }}" @selected($calendarGroupId === $group->id)>{{ $group->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tampilkan</button>
            </form>
        </div>
    @endif

    <p class="mt-2 text-sm text-slate-500">
        @if ($calendarGroupId)
            Jadwal KBM {{ $calendarGroups->firstWhere('id', $calendarGroupId)?->name }}: jenjang yang sudah punya jadwal kelompok menggantikan jadwal Daerah, jenjang lainnya tetap memakai jadwal Daerah.
            @if ($canManageOwnDefaultSchedule)
                <a href="{{ route('group-default-schedules.index') }}" class="font-semibold text-brand-700 hover:text-brand-900">Atur jadwal KBM default kelompok Anda</a>.
            @endif
        @elseif ($canManageDefault)
            Jadwal KBM default per jenjang. <a href="{{ route('default-schedules.index') }}" class="font-semibold text-brand-700 hover:text-brand-900">Atur pola jadwal default</a>.
        @else
            Referensi jadwal KBM default
            @if ($canCreateOwnSessions) dan jadwal KBM Anda. @endif
        @endif
        Klik tanggal untuk melihat agenda &amp; menambah event/musyawaroh.
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
                $dayEvents = $isCurrentMonth ? $calendarEvents->get($day->toDateString(), collect()) : collect();
                $dayMusyawarohs = $isCurrentMonth ? $calendarMusyawarohs->get($day->toDateString(), collect()) : collect();
                $hasHoliday = $matchingPatterns->contains(fn ($pattern) => $pattern->exceptionOn($day) !== null);
                $dayBadges = $matchingPatterns->unique('level_id')
                    ->map(function ($pattern) use ($day, $levelDotFor) {
                        $exception = $pattern->exceptionOn($day);
                        $time = $pattern->start_time ? substr($pattern->start_time, 0, 5).'–'.substr($pattern->end_time, 0, 5) : null;

                        return [
                            'bg' => $levelDotFor($pattern->level_id),
                            'code' => $pattern->level->code,
                            'label' => $pattern->level->name,
                            'title' => $pattern->level->name.($exception ? ' · Libur' : ($time ? ' · '.$time : '')),
                            'holiday' => $exception !== null,
                        ];
                    })
                    ->concat($daySessions->isNotEmpty() ? [[
                        'bg' => 'bg-brand-600',
                        'code' => null,
                        'icon' => 'users',
                        'label' => 'Jadwal Sendiri',
                        'title' => 'Jadwal KBM Anda'.($daySessions->first()->start_time ? ' · '.substr($daySessions->first()->start_time, 0, 5) : ''),
                        'holiday' => false,
                    ]] : [])
                    ->concat($dayEvents->map(fn ($event) => [
                        'bg' => 'bg-amber-500',
                        'code' => null,
                        'icon' => 'envelope',
                        'label' => $event->title,
                        'title' => 'Event: '.$event->title,
                        'holiday' => false,
                    ]))
                    ->concat($dayMusyawarohs->map(fn ($musyawaroh) => [
                        'bg' => 'bg-indigo-600',
                        'code' => null,
                        'icon' => 'clipboard-check',
                        'label' => $musyawaroh->title ?: 'Musyawaroh',
                        'title' => 'Musyawaroh: '.($musyawaroh->title ?: 'Tanpa judul'),
                        'holiday' => false,
                    ]));
                $maxVisibleBadges = 3;
                $visibleBadges = $dayBadges->take($maxVisibleBadges);
                $hiddenBadgeCount = max(0, $dayBadges->count() - $maxVisibleBadges);
                $cellBg = match (true) {
                    ! $isCurrentMonth => 'bg-slate-50',
                    $day->isToday() => 'bg-brand-100',
                    default => 'bg-white',
                };
                $cellClasses = 'relative flex min-h-[4.5rem] flex-col items-start gap-0.5 p-1.5 sm:min-h-[5.75rem] '.$cellBg
                    .($day->isToday() ? ' ring-1 ring-inset ring-brand-300' : '')
                    .($isSelected && ! $day->isToday() ? ' ring-2 ring-inset ring-brand-600' : '');
            @endphp

            @php
                $dayHref = route('dashboard', ['month' => $calendarMonth->format('Y-m'), 'date' => $day->toDateString(), 'group_id' => $calendarGroupId]);
            @endphp
            @if ($isCurrentMonth)
                <a href="{{ $dayHref }}" class="{{ $cellClasses }} transition hover:bg-slate-50" title="Lihat agenda tanggal ini">
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
                    <span class="flex w-full flex-col gap-0.5">
                        @foreach ($visibleBadges as $badge)
                            <span class="flex items-center gap-1" title="{{ $badge['title'] }}">
                                <span class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full ring-1 ring-white {{ $badge['bg'] }} {{ $badge['holiday'] ? 'opacity-40' : '' }}">
                                    @if ($badge['code'])
                                        <x-level-icon :code="$badge['code']" class="h-2 w-2 text-white" />
                                    @else
                                        <x-sidebar-icon :name="$badge['icon'] ?? 'users'" class="h-2 w-2 text-white" />
                                    @endif
                                </span>
                                <span class="truncate text-[0.6rem] leading-none text-slate-600">{{ $badge['label'] }}</span>
                            </span>
                        @endforeach
                        @if ($hiddenBadgeCount > 0)
                            <span class="text-[0.6rem] leading-none text-slate-400" title="{{ $hiddenBadgeCount }} jadwal lainnya">
                                +{{ $hiddenBadgeCount }} lainnya
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
                    Jadwal Sendiri
                </span>
            @endif
        </div>
    @endif

    <div id="calendar-day-modal" data-has-date="{{ request()->has('date') ? '1' : '0' }}" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-slate-900/50 p-4 pt-10 sm:items-center" role="dialog" aria-modal="true">
        <div class="w-full max-w-lg">
            <x-card class="relative max-h-[85vh] overflow-y-auto">
                <button type="button" data-close-calendar-modal class="absolute right-4 top-4 text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup">&times;</button>
                <h3 class="font-semibold text-slate-950">{{ $selectedDate->translatedFormat('l, d F Y') }}</h3>

        @if ($selectedMusyawarohs->isNotEmpty())
            <div class="mt-3 space-y-2">
                @foreach ($selectedMusyawarohs as $musyawaroh)
                    <a href="{{ route('musyawaroh.show', $musyawaroh) }}" class="flex items-center gap-3 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm transition hover:bg-indigo-100">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600">
                            <x-sidebar-icon name="clipboard-check" class="h-4 w-4 text-white" />
                        </span>
                        <span>
                            <span class="block font-semibold text-indigo-900">{{ $musyawaroh->title ?: 'Musyawaroh' }}</span>
                            <span class="block text-xs text-indigo-700">Isi daftar hadir &amp; notulen &rarr;</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($selectedEvents->isNotEmpty())
            <div class="mt-3 space-y-2">
                @foreach ($selectedEvents as $event)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm">
                        <span class="flex items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-500">
                                <x-sidebar-icon name="envelope" class="h-4 w-4 text-white" />
                            </span>
                            <span class="font-semibold text-amber-900">{{ $event->title }}</span>
                        </span>
                        <p class="mt-1 text-xs text-amber-800">{{ \Illuminate\Support\Str::limit($event->body, 120) }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($canCreateEvent || $canCreateMusyawaroh)
            <div class="mt-3 flex flex-wrap gap-2">
                @if ($canCreateEvent)
                    <a href="{{ route('announcements.index', ['event_date' => $selectedDate->toDateString()]) }}#tulis-berita" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">+ Buat Event di Tanggal Ini</a>
                @endif
                @if ($canCreateMusyawaroh)
                    <a href="{{ route('musyawaroh.create', ['meeting_date' => $selectedDate->toDateString()]) }}" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">+ Undang Musyawaroh di Tanggal Ini</a>
                @endif
            </div>
        @endif

        <div class="mt-3 space-y-2">
            <h4 class="text-sm font-semibold text-slate-700">Jadwal KBM Default</h4>
            @forelse ($selectedPatterns as $pattern)
                @php
                    $exception = $pattern->exceptionOn($selectedDate);
                    $isGroupPattern = $pattern instanceof \App\Models\GroupDefaultSchedule;
                    $canManageThisPattern = $isGroupPattern ? $canManageOwnDefaultSchedule : $canManageDefault;
                @endphp
                <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <span class="flex items-center gap-2">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $levelDotFor($pattern->level_id) }}">
                            <x-level-icon :code="$pattern->level->code" class="h-4 w-4 text-white" />
                        </span>
                        {{ $pattern->level->name }}
                        @if ($isGroupPattern) <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">Kelompok</span> @endif
                        @if ($pattern->start_time) <span class="text-slate-500">· {{ substr($pattern->start_time, 0, 5) }}–{{ substr($pattern->end_time, 0, 5) }}</span> @endif
                        @if ($exception) <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-600">Libur</span> @endif
                    </span>
                    @if ($canManageThisPattern)
                        @if ($exception)
                            <form method="POST" action="{{ route($isGroupPattern ? 'group-default-schedules.exceptions.destroy' : 'default-schedules.exceptions.destroy', $exception) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-brand-700 hover:text-brand-900">Batalkan libur</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route($isGroupPattern ? 'group-default-schedules.exceptions.store' : 'default-schedules.exceptions.store', $pattern) }}">
                                @csrf
                                <input type="hidden" name="exception_date" value="{{ $selectedDate->toDateString() }}">
                                <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Liburkan tanggal ini</button>
                            </form>
                        @endif
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">Tidak ada jadwal KBM default pada tanggal ini.</p>
            @endforelse
        </div>

        @if ($canCreateOwnSessions)
            <div class="mt-4 border-t border-slate-100 pt-4">
                <h4 class="text-sm font-semibold text-slate-700">Jadwal KBM Anda</h4>
                <div class="mt-2 space-y-2">
                    @forelse ($selectedOwnSessions as $session)
                        <div class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $session->level === \App\Models\LearningSession::LEVEL_VILLAGE ? 'Desa' : 'Kelompok' }}</span>
                                    {{ $session->start_time ? substr($session->start_time, 0, 5).'–'.substr($session->end_time ?? '', 0, 5) : 'Jam belum diisi' }}
                                    · {{ $session->classGrade?->name ?? 'Semua kelas' }}
                                    <span class="ml-1 text-xs text-slate-400">({{ $session->status }})</span>
                                </span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <a href="{{ route('learning-sessions.attendance.edit', $session) }}" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Isi Daftar Hadir &amp; Jurnal</a>
                                @if ($canFillKontrolHarian && $session->group_id)
                                    <a href="{{ route('kontrol.bacaan', ['group_id' => $session->group_id, 'class_grade_id' => $session->class_grade_id]) }}" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Kontrol Harian</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada jadwal KBM di tanggal ini.</p>
                    @endforelse
                </div>

                @if ($myVillageId)
                    <form method="POST" action="{{ route('learning-sessions.store-own-village') }}" class="mt-3 flex flex-wrap items-end gap-3">
                        @csrf
                        <input type="hidden" name="village_id" value="{{ $myVillageId }}">
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
                        <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">+ Tambah Jadwal Desa</button>
                    </form>
                @endif

                @if ($myGroupId)
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
                        <button type="submit" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-700">+ Tambah Jadwal Kelompok</button>
                    </form>
                @endif
            </div>
        @endif
            </x-card>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('calendar-day-modal');

            if (!modal) {
                return;
            }

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');

                const url = new URL(window.location.href);
                url.searchParams.delete('date');
                url.hash = '';
                window.history.replaceState({}, '', url);
            }

            if (modal.dataset.hasDate === '1') {
                openModal();
            }

            modal.querySelectorAll('[data-close-calendar-modal]').forEach(function (button) {
                button.addEventListener('click', closeModal);
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
        });
    </script>
</x-card>
