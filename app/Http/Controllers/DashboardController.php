<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ClassGrade;
use App\Models\DefaultSchedule;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\GroupDefaultSchedule;
use App\Models\LearningSession;
use App\Models\Level;
use App\Models\Munaqosah;
use App\Models\Musyawaroh;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\Ujian;
use App\Models\User;
use App\Models\Village;
use App\Support\Access;
use App\Support\StudentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $canSeeGenerus = $this->canSee($user, 'generus');
        $monthParam = $request->string('month')->value();
        $calendarMonth = preg_match('/^\d{4}-\d{2}$/', (string) $monthParam)
            ? Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth()
            : now()->startOfMonth();

        return view('dashboard.index', [
            'generusCount' => Generus::query()->when($canSeeGenerus, fn ($query) => $query->visibleTo($user))->where('status', 'active')->count(),
            'teacherCount' => Teacher::query()->when($this->canSee($user, 'teachers'), fn ($query) => $query->visibleTo($user))->where('status', 'active')->count(),
            'villageCount' => Village::query()->where('is_active', true)->count(),
            'groupCount' => Group::query()->where('is_active', true)->count(),
            'learningSessionCount' => LearningSession::count(),
            'munaqosahCount' => Munaqosah::count(),
            'trainingCount' => Training::query()->where('status', 'active')->count(),
            'announcements' => Announcement::published()->with('author')->latest('published_at')->take(5)->get(),
            'upcomingSessions' => $this->canSee($user, 'learning-sessions')
                ? LearningSession::query()
                    ->visibleTo($user)
                    ->where('session_date', '>=', now()->toDateString())
                    ->orderBy('session_date')
                    ->orderBy('start_time')
                    ->with(['teacher', 'material'])
                    ->take(5)
                    ->get()
                : collect(),
            'kontrolSemester' => $kontrolSemester = $this->canSee($user, 'ujian') ? Semester::where('is_active', true)->first() : null,
            'kontrolSummary' => $kontrolSemester ? $this->kontrolSummary($user, $kontrolSemester) : [],
            'canSeeResume' => $canSeeGenerus,
            'resumeLevels' => $canSeeGenerus ? Level::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get() : collect(),
            'resumeFilters' => [
                'gender' => $request->query('gender'),
                'level_id' => $this->resumeLevelIds($request),
            ],
        ] + ($canSeeGenerus ? $this->generusResume($user, $request) : [
            'resumeClassGrades' => collect(),
            'resumeVillages' => collect(),
            'resumeGrandCounts' => [],
            'resumeGrandTotal' => 0,
        ]) + $this->calendarData($user, $request, $calendarMonth));
    }

    /**
     * Month-grid data for the dashboard calendar: the Daerah-wide default-schedule patterns,
     * automatically overridden per jenjang by the viewed kelompok's own default-schedule
     * patterns wherever it has set one, which of the viewer's own group's ad-hoc sessions
     * (Kelompok) land on each day, and the detail panel for whichever single date is selected
     * (defaults to today).
     *
     * @return array{calendarMonth: Carbon, calendarDays: list<Carbon>, defaultSchedules: Collection, canManageDefault: bool, canCreateOwnSessions: bool, ownSessions: Collection}
     */
    private function calendarData(?User $user, Request $request, Carbon $calendarMonth): array
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $activeSemester = Semester::where('is_active', true)->first();

        $ownPlacements = $user?->scopedPlacementIds(LearningSession::OWN_MANAGE_PERMISSION) ?? [];
        $myGroupIds = $ownPlacements['group_id'] ?? [];
        $myVillageIds = $ownPlacements['village_id'] ?? [];
        $ownGroupIdsForSchedule = $user?->scopedPlacementIds(GroupDefaultScheduleController::PERMISSION)['group_id'] ?? [];
        $teacherGroupId = $user?->teacher?->group_id;

        if ($teacherGroupId !== null) {
            $ownGroupIdsForSchedule = array_values(array_unique([...$ownGroupIdsForSchedule, $teacherGroupId]));
        }

        $calendarGroups = $this->visibleGroups($user, $ownGroupIdsForSchedule);
        $calendarGroupId = $calendarGroups->firstWhere('id', $request->integer('group_id'))?->id
            ?? $calendarGroups->firstWhere('id', $ownGroupIdsForSchedule[0] ?? null)?->id
            ?? ($calendarGroups->count() === 1 ? $calendarGroups->first()->id : null);

        $daerahSchedules = ($activeAcademicYear && $activeSemester)
            ? DefaultSchedule::with(['level', 'exceptions'])
                ->where('academic_year_id', $activeAcademicYear->id)
                ->where('semester_id', $activeSemester->id)
                ->get()
            : collect();

        $groupSchedules = ($calendarGroupId && $activeAcademicYear && $activeSemester)
            ? GroupDefaultSchedule::with(['level', 'exceptions'])
                ->where('group_id', $calendarGroupId)
                ->where('academic_year_id', $activeAcademicYear->id)
                ->where('semester_id', $activeSemester->id)
                ->get()
            : collect();

        $overriddenLevelIds = $groupSchedules->pluck('level_id')->all();
        $defaultSchedules = $daerahSchedules
            ->reject(fn ($pattern) => in_array($pattern->level_id, $overriddenLevelIds, true))
            ->concat($groupSchedules)
            ->values();

        $ownSessionsQuery = $this->ownSessionsQuery($myGroupIds, $myVillageIds);

        $ownSessions = $ownSessionsQuery
            ? $ownSessionsQuery
                ->whereBetween('session_date', [$calendarMonth->toDateString(), $calendarMonth->copy()->endOfMonth()->toDateString()])
                ->with('classGrade')
                ->get()
                ->groupBy(fn (LearningSession $session): string => substr((string) $session->session_date, 0, 10))
            : collect();

        $dateParam = $request->string('date')->value();
        $selectedDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $dateParam)
            ? Carbon::createFromFormat('Y-m-d', $dateParam)
            : now()->startOfDay();

        $monthStart = $calendarMonth->toDateString();
        $monthEnd = $calendarMonth->copy()->endOfMonth()->toDateString();

        $calendarEvents = $this->eventsQuery($user)
            ->whereBetween('event_date', [$monthStart, $monthEnd])
            ->get()
            ->groupBy(fn (Announcement $event): string => $event->event_date->toDateString());

        $calendarMusyawarohs = $this->musyawarohsQuery($user)
            ->whereBetween('meeting_date', [$monthStart, $monthEnd])
            ->get()
            ->groupBy(fn (Musyawaroh $musyawaroh): string => $musyawaroh->meeting_date->toDateString());

        return [
            'calendarMonth' => $calendarMonth,
            'calendarDays' => $this->buildCalendarDays($calendarMonth),
            'defaultSchedules' => $defaultSchedules,
            'calendarGroups' => $calendarGroups,
            'calendarGroupId' => $calendarGroupId,
            'canManageOwnDefaultSchedule' => in_array($calendarGroupId, $ownGroupIdsForSchedule, true),
            'canManageDefault' => $user?->hasGlobalAccess(LearningSession::MANAGE_PERMISSION) ?? false,
            'canCreateOwnSessions' => $myGroupIds !== [] || $myVillageIds !== [],
            'myGroupId' => $myGroupIds[0] ?? null,
            'myVillageId' => $myVillageIds[0] ?? null,
            'canFillKontrolHarian' => $user?->hasPermission('manage-ujian') ?? false,
            'ownSessions' => $ownSessions,
            'calendarClassGrades' => ($myGroupIds !== [] || $myVillageIds !== []) ? ClassGrade::where('is_active', true)->orderBy('sort_order')->get() : collect(),
            'selectedDate' => $selectedDate,
            'selectedPatterns' => $defaultSchedules->filter(fn ($pattern): bool => $pattern->appliesTo($selectedDate)),
            'selectedOwnSessions' => $selectedOwnSessions = $this->ownSessionsQuery($myGroupIds, $myVillageIds)
                ?->where('session_date', $selectedDate->toDateString())->with('classGrade')->get() ?? collect(),
            'selectedOwnSessionCategories' => $selectedOwnSessions->mapWithKeys(fn (LearningSession $session): array => [
                $session->id => $this->kontrolCategoriesForSession($session, $activeAcademicYear, $activeSemester),
            ]),
            'calendarEvents' => $calendarEvents,
            'calendarMusyawarohs' => $calendarMusyawarohs,
            'selectedEvents' => $calendarEvents->get($selectedDate->toDateString(), collect()),
            'selectedMusyawarohs' => $calendarMusyawarohs->get($selectedDate->toDateString(), collect()),
            'canCreateEvent' => $user?->hasPermission('manage-announcements') ?? false,
            'canCreateMusyawaroh' => $user?->hasPermission('manage-tasks') ?? false,
        ];
    }

    /**
     * Published, date-anchored announcements the viewer may see on the calendar: every one for
     * a manage-announcements holder, otherwise only those with no chosen audience (public to
     * all) or where the viewer is one of the chosen recipients.
     *
     * @return Builder<Announcement>
     */
    private function eventsQuery(?User $user): Builder
    {
        $query = Announcement::published()->whereNotNull('event_date');

        if ($user?->hasPermission('manage-announcements')) {
            return $query;
        }

        return $query->where(function (Builder $scoped) use ($user): void {
            $scoped->whereDoesntHave('recipients');

            if ($user !== null) {
                $scoped->orWhereHas('recipients', fn (Builder $r) => $r->whereKey($user->id));
            }
        });
    }

    /**
     * @return Builder<Musyawaroh>
     */
    private function musyawarohsQuery(?User $user): Builder
    {
        if ($user === null) {
            return Musyawaroh::whereRaw('1 = 0');
        }

        return Musyawaroh::visibleTo($user);
    }

    /**
     * Sessions in the viewer's own group and/or village placements (from
     * manage-own-learning-sessions), or null when they hold neither.
     *
     * @param  list<int>  $groupIds
     * @param  list<int>  $villageIds
     * @return Builder<LearningSession>|null
     */
    private function ownSessionsQuery(array $groupIds, array $villageIds): ?Builder
    {
        if ($groupIds === [] && $villageIds === []) {
            return null;
        }

        return LearningSession::where(function (Builder $scoped) use ($groupIds, $villageIds): void {
            $scoped->whereRaw('1 = 0');

            if ($groupIds !== []) {
                $scoped->orWhere(fn (Builder $q) => $q->where('level', LearningSession::LEVEL_GROUP)->whereIn('group_id', $groupIds));
            }

            if ($villageIds !== []) {
                $scoped->orWhere(fn (Builder $q) => $q->where('level', LearningSession::LEVEL_VILLAGE)->whereIn('village_id', $villageIds));
            }
        });
    }

    /**
     * The material classifications (bacaan/hafalan/dst., per KontrolController::CATEGORIES)
     * tagged on whichever default-schedule pattern this session was generated from — the
     * group's own pattern for its jenjang when it has one, else the Daerah-wide pattern —
     * so the calendar can link straight into the matching Kontrol Harian submenu(s). Falls
     * back to 'bacaan' when the session's class has no resolvable jenjang or no pattern
     * tags any classification, keeping the link that existed before this feature.
     *
     * @return list<string>
     */
    private function kontrolCategoriesForSession(LearningSession $session, ?AcademicYear $academicYear, ?Semester $semester): array
    {
        $levelId = $session->classGrade?->level_id;

        if ($levelId === null || $academicYear === null || $semester === null) {
            return ['bacaan'];
        }

        $sessionDate = Carbon::parse($session->session_date);
        $pattern = null;

        if ($session->level === LearningSession::LEVEL_GROUP && $session->group_id) {
            $pattern = GroupDefaultSchedule::where('group_id', $session->group_id)
                ->where('level_id', $levelId)
                ->where('academic_year_id', $academicYear->id)
                ->where('semester_id', $semester->id)
                ->get()
                ->first(fn (GroupDefaultSchedule $candidate): bool => $candidate->appliesTo($sessionDate));
        }

        if ($pattern === null) {
            $pattern = DefaultSchedule::where('level_id', $levelId)
                ->where('academic_year_id', $academicYear->id)
                ->where('semester_id', $semester->id)
                ->get()
                ->first(fn (DefaultSchedule $candidate): bool => $candidate->appliesTo($sessionDate));
        }

        $categories = $pattern?->categories ?? [];

        return $categories === [] ? ['bacaan'] : $categories;
    }

    /**
     * Groups the viewer may pick on the calendar to see whether that kelompok overrides the
     * Daerah-wide schedule: every active group for a Daerah-wide (global) viewer, the groups
     * in a desa-scoped viewer's own village, and a kelompok-scoped/teacher viewer's own group.
     *
     * @param  list<int>  $ownGroupIds
     * @return Collection<int, Group>
     */
    private function visibleGroups(?User $user, array $ownGroupIds): Collection
    {
        if ($user === null) {
            return collect();
        }

        $query = Group::query()->where('is_active', true)->orderBy('name');
        $activeScopes = $user->activeScopes();

        if ($activeScopes->contains('type', 'global')) {
            return $query->get();
        }

        $villageIds = $activeScopes->where('type', 'village')->pluck('id')->filter()->unique()->values()->all();

        if ($villageIds === [] && $ownGroupIds === []) {
            return collect();
        }

        return $query->where(function (Builder $scoped) use ($villageIds, $ownGroupIds): void {
            $scoped->whereRaw('1 = 0');

            if ($villageIds !== []) {
                $scoped->orWhereIn('village_id', $villageIds);
            }

            if ($ownGroupIds !== []) {
                $scoped->orWhereIn('id', $ownGroupIds);
            }
        })->get();
    }

    /**
     * Every day shown on the month grid: full weeks (Senin–Minggu) from before the 1st
     * through after the last day, so the calendar has no partial rows.
     *
     * @return list<Carbon>
     */
    private function buildCalendarDays(Carbon $calendarMonth): array
    {
        $start = $calendarMonth->copy()->startOfWeek(Carbon::MONDAY);
        $end = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $days = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $days[] = $day->copy();
        }

        return $days;
    }

    /**
     * Breakdown of active generus per desa/kelompok and kelas KBM (jenjang), honoring the
     * same gender/jenjang filters the dashboard's resume table exposes and the same
     * village/group scope the user's roles already grant them elsewhere.
     *
     * @return array{resumeClassGrades: Collection, resumeVillages: Collection, resumeGrandCounts: array<int, int>, resumeGrandTotal: int}
     */
    private function generusResume(?User $user, Request $request): array
    {
        $gender = $request->query('gender');
        $levelIds = $this->resumeLevelIds($request);

        $classGrades = ClassGrade::query()
            ->join('levels', 'levels.id', '=', 'class_grades.level_id')
            ->where('class_grades.is_active', true)
            ->where('levels.is_active', true)
            ->when($levelIds !== [], fn (Builder $query) => $query->whereIn('class_grades.level_id', $levelIds))
            ->orderBy('levels.sort_order')
            ->orderBy('levels.id')
            ->orderBy('class_grades.sort_order')
            ->orderBy('class_grades.id')
            ->select('class_grades.id', 'class_grades.name', 'levels.name as level_name')
            ->get();

        $classGradeIds = $classGrades->pluck('id')->all();

        $permissions = ['view-generus', Generus::MANAGE_PERMISSION];
        $restricted = $user !== null && ! $user->hasGlobalAccessAny($permissions);
        $placementIds = $restricted ? $user->scopedPlacementIdsAny($permissions) : [];

        $groups = Group::query()
            ->where('is_active', true)
            ->when($restricted, function (Builder $query) use ($placementIds): void {
                $query->where(function (Builder $scoped) use ($placementIds): void {
                    $scoped->whereRaw('1 = 0');

                    if (isset($placementIds['group_id'])) {
                        $scoped->orWhereIn('id', $placementIds['group_id']);
                    }

                    if (isset($placementIds['village_id'])) {
                        $scoped->orWhereIn('village_id', $placementIds['village_id']);
                    }
                });
            })
            ->orderBy('name')
            ->get();

        $villages = $groups->isEmpty()
            ? collect()
            : Village::query()->whereIn('id', $groups->pluck('village_id')->unique())->orderBy('name')->get();

        $counts = $groups->isEmpty() || $classGradeIds === []
            ? collect()
            : GenerusAssignment::query()
                ->join('generus', 'generus.id', '=', 'generus_assignments.generus_id')
                ->whereNull('generus_assignments.ended_at')
                ->whereNull('generus.deleted_at')
                ->where('generus.status', 'active')
                ->whereIn('generus.learning_class_id', $classGradeIds)
                ->whereIn('generus_assignments.group_id', $groups->pluck('id'))
                ->when($gender, fn (Builder $query) => $query->where('generus.gender', $gender))
                ->selectRaw('generus_assignments.group_id as group_id, generus.learning_class_id as class_grade_id, count(*) as total')
                ->groupBy('generus_assignments.group_id', 'generus.learning_class_id')
                ->get();

        $countsByGroup = $counts->groupBy('group_id');

        $resumeVillages = $villages->map(function (Village $village) use ($groups, $countsByGroup, $classGradeIds): array {
            $groupRows = $groups->where('village_id', $village->id)->values()->map(function (Group $group) use ($countsByGroup, $classGradeIds): array {
                $classTotals = $countsByGroup->get($group->id, collect())->pluck('total', 'class_grade_id');

                $counts = [];
                foreach ($classGradeIds as $classGradeId) {
                    $counts[$classGradeId] = (int) ($classTotals[$classGradeId] ?? 0);
                }

                return [
                    'group' => $group,
                    'counts' => $counts,
                    'total' => array_sum($counts),
                ];
            });

            $villageCounts = [];
            foreach ($classGradeIds as $classGradeId) {
                $villageCounts[$classGradeId] = $groupRows->sum(fn (array $row): int => $row['counts'][$classGradeId]);
            }

            return [
                'village' => $village,
                'groups' => $groupRows,
                'counts' => $villageCounts,
                'total' => array_sum($villageCounts),
            ];
        });

        $grandCounts = [];
        foreach ($classGradeIds as $classGradeId) {
            $grandCounts[$classGradeId] = $resumeVillages->sum(fn (array $row): int => $row['counts'][$classGradeId]);
        }

        return [
            'resumeClassGrades' => $classGrades,
            'resumeVillages' => $resumeVillages,
            'resumeGrandCounts' => $grandCounts,
            'resumeGrandTotal' => array_sum($grandCounts),
        ];
    }

    /**
     * Rata-rata & jumlah nilai kontrol (Ujian bertaut materi RPP) per kategori target generus,
     * pada semester aktif, disaring ke generus yang boleh dilihat user (kelompok/desa/daerah).
     *
     * @return list<array{category: string, label: string, average: ?float, count: int}>
     */
    private function kontrolSummary(?User $user, Semester $semester): array
    {
        $viewIds = StudentScope::viewIds($user, Ujian::MANAGE_PERMISSION);

        $categoryQuery = function (string $code) use ($semester, $viewIds): Builder {
            return StudentScope::limit(
                Ujian::query()
                    ->whereHas('material.materialChapter.materialCategory', fn (Builder $query) => $query->where('code', $code))
                    ->where('semester_id', $semester->id)
                    ->whereNotNull('score'),
                $viewIds,
            );
        };

        return collect(KontrolController::CATEGORIES)
            ->map(fn (string $label, string $code): array => [
                'category' => $code,
                'label' => $label,
                'average' => $categoryQuery($code)->avg('score'),
                'count' => $categoryQuery($code)->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * The jenjang filter accepts several levels at once (`level_id[]=1&level_id[]=2`).
     *
     * @return list<int>
     */
    private function resumeLevelIds(Request $request): array
    {
        return collect($request->query('level_id', []))
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Counts follow the user's area only when they hold a view/manage role for that data.
     * An anonymous visitor is checked against the guest role's own permissions, the same
     * way the `permission:` route middleware already grants them read-only access.
     */
    private function canSee(?User $user, string $module): bool
    {
        if ($user !== null) {
            return $user->hasPermission("view-{$module}") || $user->hasPermission("manage-{$module}");
        }

        return Access::can("view-{$module}", "manage-{$module}");
    }
}
