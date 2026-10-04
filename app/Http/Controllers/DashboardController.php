<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Generus;
use App\Models\Group;
use App\Models\LearningSession;
use App\Models\Munaqosah;
use App\Models\Region;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
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
        ]);
    }

    /**
     * Counts follow the user's area only when they hold a view/manage role for that data.
     */
    private function canSee(?User $user, string $module): bool
    {
        return $user !== null && ($user->hasPermission("view-{$module}") || $user->hasPermission("manage-{$module}"));
    }
}
