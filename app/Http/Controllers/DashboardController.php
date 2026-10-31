<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\ClassGrade;
use App\Models\Generus;
use App\Models\GenerusAssignment;
use App\Models\Group;
use App\Models\LearningSession;
use App\Models\Level;
use App\Models\Munaqosah;
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
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $canSeeGenerus = $this->canSee($user, 'generus');

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
        ]));
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
