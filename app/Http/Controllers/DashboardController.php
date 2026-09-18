<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\Group;
use App\Models\LearningSession;
use App\Models\Munaqosah;
use App\Models\Region;
use App\Models\Teacher;
use App\Models\Training;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard.index', [
            'generusCount' => Generus::query()->where('status', 'active')->count(),
            'teacherCount' => Teacher::query()->where('status', 'active')->count(),
            'regionCount' => Region::query()->where('is_active', true)->count(),
            'groupCount' => Group::query()->where('is_active', true)->count(),
            'learningSessionCount' => LearningSession::count(),
            'munaqosahCount' => Munaqosah::count(),
            'trainingCount' => Training::query()->where('status', 'active')->count(),
        ]);
    }
}
