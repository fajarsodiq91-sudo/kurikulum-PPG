@php
    $dayLabels = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    $levelColors = ['bg-amber-100 text-amber-700', 'bg-sky-100 text-sky-700', 'bg-emerald-100 text-emerald-700', 'bg-violet-100 text-violet-700', 'bg-rose-100 text-rose-700'];
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
            </p>
        </div>
        <div class="flex items-center gap-2 text-sm font-semibold">
            <a href="{{ route('dashboard', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">&laquo; Sebelumnya</a>
            <a href="{{ route('dashboard') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">Hari Ini</a>
            <a href="{{ route('dashboard', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-slate-700 hover:bg-slate-50">Berikutnya &raquo;</a>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 text-xs">
        @foreach ($dayLabels as $label)
            <div class="bg-slate-50 px-2 py-2 text-center font-semibold text-slate-500">{{ $label }}</div>
        @endforeach

        @foreach ($calendarDays as $day)
            @php
                $isCurrentMonth = $day->month === $calendarMonth->month;
                $matchingPatterns = $defaultSchedules->filter(fn ($pattern) => $pattern->occursOn($day));
                $daySessions = $ownSessions->get($day->toDateString(), collect());
            @endphp
            <div class="min-h-[6.5rem] bg-white p-1.5 {{ $isCurrentMonth ? '' : 'bg-slate-50 text-slate-400' }}">
                <p class="text-right text-[0.7rem] font-semibold {{ $day->isToday() ? 'text-amber-600' : '' }}">{{ $day->day }}</p>

                <div class="mt-1 space-y-1">
                    @foreach ($matchingPatterns as $pattern)
                        @php
                            $exception = $pattern->exceptions->first(fn ($e) => $e->exception_date->isSameDay($day));
                        @endphp
                        <div class="flex items-center justify-between gap-1 rounded px-1.5 py-0.5 {{ $levelColorFor($pattern->level_id) }}">
                            <span class="truncate">{{ $pattern->level->name }}</span>
                            @if ($canManageDefault)
                                @if ($exception)
                                    <form method="POST" action="{{ route('default-schedules.exceptions.destroy', $exception) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Batalkan libur" class="font-bold">↺</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('default-schedules.exceptions.store', $pattern) }}">
                                        @csrf
                                        <input type="hidden" name="exception_date" value="{{ $day->toDateString() }}">
                                        <button type="submit" title="Liburkan tanggal ini" class="font-bold">×</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @endforeach

                    @foreach ($daySessions as $session)
                        <p class="truncate rounded bg-brand-50 px-1.5 py-0.5 text-brand-700">
                            {{ $session->start_time ? substr($session->start_time, 0, 5).' · ' : '' }}{{ $session->classGrade?->name ?? 'KBM' }}
                        </p>
                    @endforeach
                </div>

                @if ($canCreateOwnSessions)
                    <details class="mt-1">
                        <summary class="cursor-pointer select-none text-[0.7rem] font-semibold text-slate-400 hover:text-brand-700">+ Tambah</summary>
                        <form method="POST" action="{{ route('learning-sessions.store-own') }}" class="mt-1 space-y-1">
                            @csrf
                            <input type="hidden" name="group_id" value="{{ $myGroupId }}">
                            <input type="hidden" name="session_date" value="{{ $day->toDateString() }}">
                            <select name="class_grade_id" class="w-full rounded border border-slate-300 px-1 py-0.5 text-[0.7rem]">
                                <option value="">-- Kelas KBM --</option>
                                @foreach ($calendarClassGrades as $classGrade)
                                    <option value="{{ $classGrade->id }}">{{ $classGrade->name }}</option>
                                @endforeach
                            </select>
                            <div class="flex gap-1">
                                <input type="time" name="start_time" class="w-1/2 rounded border border-slate-300 px-1 py-0.5 text-[0.7rem]">
                                <input type="time" name="end_time" class="w-1/2 rounded border border-slate-300 px-1 py-0.5 text-[0.7rem]">
                            </div>
                            <button type="submit" class="w-full rounded bg-brand-600 px-1 py-0.5 text-[0.7rem] font-semibold text-white hover:bg-brand-700">Simpan</button>
                        </form>
                    </details>
                @endif
            </div>
        @endforeach
    </div>
</x-card>
