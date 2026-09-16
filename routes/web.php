<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'permission:view-dashboard'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

Route::middleware(['auth', 'permission:view-reports'])->group(function () {
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])
        ->name('reports.index');
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

Route::middleware(['auth', 'permission:manage-training'])->group(function () {
    Route::get('/trainings', [\App\Http\Controllers\TrainingController::class, 'index'])
        ->name('trainings.index');
    Route::post('/trainings', [\App\Http\Controllers\TrainingController::class, 'store'])
        ->name('trainings.store');
});

Route::middleware(['auth', 'permission:manage-communication'])->group(function () {
    Route::get('/communications', [\App\Http\Controllers\CommunicationController::class, 'index'])
        ->name('communications.index');
    Route::post('/communications', [\App\Http\Controllers\CommunicationController::class, 'store'])
        ->name('communications.store');
});

Route::middleware(['auth', 'permission:manage-munaqosah'])->group(function () {
    Route::get('/munaqosahs', [\App\Http\Controllers\MunaqosahController::class, 'index'])
        ->name('munaqosahs.index');
    Route::post('/munaqosahs', [\App\Http\Controllers\MunaqosahController::class, 'store'])
        ->name('munaqosahs.store');
});

Route::middleware(['auth', 'permission:manage-report-cards'])->group(function () {
    Route::get('/report-cards', [\App\Http\Controllers\ReportCardController::class, 'index'])
        ->name('report-cards.index');
    Route::post('/report-cards', [\App\Http\Controllers\ReportCardController::class, 'store'])
        ->name('report-cards.store');
});

Route::middleware(['auth', 'permission:manage-follow-ups'])->group(function () {
    Route::get('/follow-ups', [\App\Http\Controllers\FollowUpController::class, 'index'])
        ->name('follow-ups.index');
    Route::post('/follow-ups', [\App\Http\Controllers\FollowUpController::class, 'store'])
        ->name('follow-ups.store');
});

Route::middleware(['auth', 'permission:manage-progress-tracking'])->group(function () {
    Route::get('/progress-tracks', [\App\Http\Controllers\ProgressTrackController::class, 'index'])
        ->name('progress-tracks.index');
    Route::post('/progress-tracks', [\App\Http\Controllers\ProgressTrackController::class, 'store'])
        ->name('progress-tracks.store');
});

Route::middleware(['auth', 'permission:manage-milestones'])->group(function () {
    Route::get('/milestones', [\App\Http\Controllers\MilestoneController::class, 'index'])
        ->name('milestones.index');
    Route::post('/milestones', [\App\Http\Controllers\MilestoneController::class, 'store'])
        ->name('milestones.store');
});

Route::middleware(['auth', 'permission:manage-annual-audit'])->group(function () {
    Route::get('/annual-audits', [\App\Http\Controllers\AnnualAuditController::class, 'index'])
        ->name('annual-audits.index');
    Route::post('/annual-audits', [\App\Http\Controllers\AnnualAuditController::class, 'store'])
        ->name('annual-audits.store');
});

Route::middleware(['auth', 'permission:manage-organization-units'])->group(function () {
    Route::get('/organization-units', [\App\Http\Controllers\OrganizationUnitController::class, 'index'])
        ->name('organization-units.index');
    Route::post('/organization-units', [\App\Http\Controllers\OrganizationUnitController::class, 'store'])
        ->name('organization-units.store');
});

Route::middleware(['auth', 'permission:manage-assignments'])->group(function () {
    Route::get('/assignments', [\App\Http\Controllers\AssignmentController::class, 'index'])
        ->name('assignments.index');
    Route::post('/assignments', [\App\Http\Controllers\AssignmentController::class, 'store'])
        ->name('assignments.store');
});

Route::middleware(['auth', 'permission:manage-activity-schedules'])->group(function () {
    Route::get('/activity-schedules', [\App\Http\Controllers\ActivityScheduleController::class, 'index'])
        ->name('activity-schedules.index');
    Route::post('/activity-schedules', [\App\Http\Controllers\ActivityScheduleController::class, 'store'])
        ->name('activity-schedules.store');
});

Route::middleware(['auth', 'permission:manage-activity-executions'])->group(function () {
    Route::get('/activity-executions', [\App\Http\Controllers\ActivityExecutionController::class, 'index'])
        ->name('activity-executions.index');
    Route::post('/activity-executions', [\App\Http\Controllers\ActivityExecutionController::class, 'store'])
        ->name('activity-executions.store');
});
