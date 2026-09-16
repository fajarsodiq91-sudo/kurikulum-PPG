<?php

namespace App\Http\Controllers;

use App\Models\Generus;
use App\Models\Teacher;
use App\Models\Training;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'generusCount' => Generus::count(),
            'teacherCount' => Teacher::count(),
            'trainingCount' => Training::count(),
            'trainings' => Training::with('academicYear')->latest()->take(5)->get(),
        ]);
    }
}
