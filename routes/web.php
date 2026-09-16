<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'permission:view-dashboard'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

Route::middleware(['auth', 'permission:view-master-data'])->group(function () {
    Route::get('/master-data/regions', [\App\Http\Controllers\MasterData\RegionController::class, 'index'])
        ->name('master-data.regions.index');
    Route::post('/master-data/regions', [\App\Http\Controllers\MasterData\RegionController::class, 'store'])
        ->name('master-data.regions.store');
});

Route::middleware(['auth', 'permission:manage-generus'])->group(function () {
    Route::get('/generus', [\App\Http\Controllers\GenerusController::class, 'index'])
        ->name('generus.index');
    Route::get('/generus/create', [\App\Http\Controllers\GenerusController::class, 'create'])
        ->name('generus.create');
    Route::post('/generus', [\App\Http\Controllers\GenerusController::class, 'store'])
        ->name('generus.store');
});

Route::middleware(['auth', 'permission:manage-teachers'])->group(function () {
    Route::get('/teachers', [\App\Http\Controllers\TeacherController::class, 'index'])
        ->name('teachers.index');
    Route::post('/teachers', [\App\Http\Controllers\TeacherController::class, 'store'])
        ->name('teachers.store');
});

Route::middleware(['auth', 'permission:manage-guardians'])->group(function () {
    Route::get('/guardians', [\App\Http\Controllers\GuardianController::class, 'index'])
        ->name('guardians.index');
    Route::post('/guardians', [\App\Http\Controllers\GuardianController::class, 'store'])
        ->name('guardians.store');
});

Route::middleware(['auth', 'permission:manage-curriculum'])->group(function () {
    Route::get('/curriculum-programs', [\App\Http\Controllers\CurriculumProgramController::class, 'index'])
        ->name('curriculum-programs.index');
    Route::post('/curriculum-programs', [\App\Http\Controllers\CurriculumProgramController::class, 'store'])
        ->name('curriculum-programs.store');
});

Route::middleware(['auth', 'permission:manage-learning-materials'])->group(function () {
    Route::get('/learning-materials', [\App\Http\Controllers\LearningMaterialController::class, 'index'])
        ->name('learning-materials.index');
    Route::post('/learning-materials', [\App\Http\Controllers\LearningMaterialController::class, 'store'])
        ->name('learning-materials.store');
});

Route::middleware(['auth', 'permission:manage-learning-sessions'])->group(function () {
    Route::get('/learning-sessions', [\App\Http\Controllers\LearningSessionController::class, 'index'])
        ->name('learning-sessions.index');
    Route::post('/learning-sessions', [\App\Http\Controllers\LearningSessionController::class, 'store'])
        ->name('learning-sessions.store');
});

Route::middleware(['auth', 'permission:manage-learning-attendance'])->group(function () {
    Route::get('/session-attendances', [\App\Http\Controllers\SessionAttendanceController::class, 'index'])
        ->name('session-attendances.index');
    Route::post('/session-attendances', [\App\Http\Controllers\SessionAttendanceController::class, 'store'])
        ->name('session-attendances.store');
});

Route::middleware(['auth', 'permission:manage-evaluations'])->group(function () {
    Route::get('/evaluations', [\App\Http\Controllers\EvaluationController::class, 'index'])
        ->name('evaluations.index');
    Route::post('/evaluations', [\App\Http\Controllers\EvaluationController::class, 'store'])
        ->name('evaluations.store');

    Route::get('/evaluation-scores', [\App\Http\Controllers\EvaluationScoreController::class, 'index'])
        ->name('evaluation-scores.index');
    Route::post('/evaluation-scores', [\App\Http\Controllers\EvaluationScoreController::class, 'store'])
        ->name('evaluation-scores.store');
});
