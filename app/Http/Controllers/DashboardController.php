<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Generus;
use App\Models\Group;
use App\Models\LearningSession;
use App\Models\Munaqosah;
use App\Models\Region;
use App\Models\Semester;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\Ujian;
use App\Models\User;
use App\Support\StudentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.index', [
            'generusCount' => Generus::query()->when($this->canSee($user, 'generus'), fn ($query) => $query->visibleTo($user))->where('status', 'active')->count(),
            'teacherCount' => Teacher::query()->when($this->canSee($user, 'teachers'), fn ($query) => $query->visibleTo($user))->where('status', 'active')->count(),
            'regionCount' => Region::query()->where('is_active', true)->count(),
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
        ]);
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
     * Counts follow the user's area only when they hold a view/manage role for that data.
     */
    private function canSee(?User $user, string $module): bool
    {
        return $user !== null && ($user->hasPermission("view-{$module}") || $user->hasPermission("manage-{$module}"));
    }
}
