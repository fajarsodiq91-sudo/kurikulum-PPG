<?php

use App\Http\Controllers\ActivityExecutionController;
use App\Http\Controllers\ActivityScheduleController;
use App\Http\Controllers\AnnualAuditController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CurriculumProgramController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\EvaluationScoreController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\GenerusController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\LearningSessionController;
use App\Http\Controllers\MasterData\MasterDataController;
use App\Http\Controllers\MasterData\RegionController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\MunaqosahController;
use App\Http\Controllers\OrganizationUnitController;
use App\Http\Controllers\ProgressTrackController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionAttendanceController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'permission:view-dashboard'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

Route::middleware(['auth', 'permission:manage-users'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');
    Route::post('/users', [UserController::class, 'store'])
        ->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->whereNumber('user')
        ->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->whereNumber('user')
        ->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->whereNumber('user')
        ->name('users.destroy');

    Route::get('/roles', [RoleController::class, 'index'])
        ->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])
        ->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
        ->whereNumber('role')
        ->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->whereNumber('role')
        ->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->whereNumber('role')
        ->name('roles.destroy');
});

Route::middleware(['auth', 'permission:view-reports'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])
        ->name('reports.index');
});

Route::middleware(['auth', 'permission:view-master-data'])->group(function () {
    Route::get('/master-data', [MasterDataController::class, 'index'])
        ->name('master-data.index');
    Route::post('/master-data/regions', [MasterDataController::class, 'storeRegion'])
        ->name('master-data.regions.store');
    Route::post('/master-data/villages', [MasterDataController::class, 'storeVillage'])
        ->name('master-data.villages.store');
    Route::post('/master-data/groups', [MasterDataController::class, 'storeGroup'])
        ->name('master-data.groups.store');
    Route::post('/master-data/levels', [MasterDataController::class, 'storeLevel'])
        ->name('master-data.levels.store');
    Route::post('/master-data/academic-years', [MasterDataController::class, 'storeAcademicYear'])
        ->name('master-data.academic-years.store');
    Route::post('/master-data/semesters', [MasterDataController::class, 'storeSemester'])
        ->name('master-data.semesters.store');
    Route::get('/master-data/regions', [RegionController::class, 'index'])
        ->name('master-data.regions.index');
});

Route::middleware(['auth', 'permission:manage-generus'])->group(function () {
    Route::get('/generus', [GenerusController::class, 'index'])
        ->name('generus.index');
    Route::get('/generus/create', [GenerusController::class, 'create'])
        ->name('generus.create');
    Route::post('/generus', [GenerusController::class, 'store'])
        ->name('generus.store');
    Route::post('/generus/import', [GenerusController::class, 'import'])
        ->name('generus.import');
    Route::get('/generus/export', [GenerusController::class, 'export'])
        ->name('generus.export');
    Route::get('/generus/{generus}', [GenerusController::class, 'show'])
        ->whereNumber('generus')
        ->name('generus.show');
    Route::get('/generus/{generus}/id-card', [GenerusController::class, 'idCard'])
        ->whereNumber('generus')
        ->name('generus.id-card');
    Route::get('/generus/{generus}/edit', [GenerusController::class, 'edit'])
        ->whereNumber('generus')
        ->name('generus.edit');
    Route::put('/generus/{generus}', [GenerusController::class, 'update'])
        ->whereNumber('generus')
        ->name('generus.update');
    Route::delete('/generus/{generus}', [GenerusController::class, 'destroy'])
        ->whereNumber('generus')
        ->name('generus.destroy');
});

Route::middleware(['auth', 'permission:manage-teachers'])->group(function () {
    Route::get('/teachers', [TeacherController::class, 'index'])
        ->name('teachers.index');
    Route::post('/teachers', [TeacherController::class, 'store'])
        ->name('teachers.store');
    Route::get('/teachers/{teacher}/edit', [TeacherController::class, 'edit'])
        ->whereNumber('teacher')
        ->name('teachers.edit');
    Route::get('/teachers/{teacher}/id-card', [TeacherController::class, 'idCard'])
        ->whereNumber('teacher')
        ->name('teachers.id-card');
    Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])
        ->whereNumber('teacher')
        ->name('teachers.update');
    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])
        ->whereNumber('teacher')
        ->name('teachers.destroy');
});

Route::middleware(['auth', 'permission:manage-guardians'])->group(function () {
    Route::get('/guardians', [GuardianController::class, 'index'])
        ->name('guardians.index');
    Route::post('/guardians', [GuardianController::class, 'store'])
        ->name('guardians.store');
    Route::get('/guardians/{guardian}/edit', [GuardianController::class, 'edit'])
        ->whereNumber('guardian')
        ->name('guardians.edit');
    Route::put('/guardians/{guardian}', [GuardianController::class, 'update'])
        ->whereNumber('guardian')
        ->name('guardians.update');
    Route::delete('/guardians/{guardian}', [GuardianController::class, 'destroy'])
        ->whereNumber('guardian')
        ->name('guardians.destroy');
});

Route::middleware(['auth', 'permission:manage-curriculum'])->group(function () {
    Route::get('/curriculum-programs', [CurriculumProgramController::class, 'index'])
        ->name('curriculum-programs.index');
    Route::post('/curriculum-programs', [CurriculumProgramController::class, 'store'])
        ->name('curriculum-programs.store');
});

Route::middleware(['auth', 'permission:manage-learning-materials'])->group(function () {
    Route::get('/learning-materials', [LearningMaterialController::class, 'index'])
        ->name('learning-materials.index');
    Route::post('/learning-materials', [LearningMaterialController::class, 'store'])
        ->name('learning-materials.store');
});

Route::middleware(['auth', 'permission:manage-learning-sessions'])->group(function () {
    Route::get('/learning-sessions', [LearningSessionController::class, 'index'])
        ->name('learning-sessions.index');
    Route::post('/learning-sessions', [LearningSessionController::class, 'store'])
        ->name('learning-sessions.store');
});

Route::middleware(['auth', 'permission:manage-learning-attendance'])->group(function () {
    Route::get('/session-attendances', [SessionAttendanceController::class, 'index'])
        ->name('session-attendances.index');
    Route::post('/session-attendances', [SessionAttendanceController::class, 'store'])
        ->name('session-attendances.store');
    Route::get('/session-attendances/{sessionAttendance}/edit', [SessionAttendanceController::class, 'edit'])
        ->whereNumber('sessionAttendance')
        ->name('session-attendances.edit');
    Route::put('/session-attendances/{sessionAttendance}', [SessionAttendanceController::class, 'update'])
        ->whereNumber('sessionAttendance')
        ->name('session-attendances.update');
    Route::delete('/session-attendances/{sessionAttendance}', [SessionAttendanceController::class, 'destroy'])
        ->whereNumber('sessionAttendance')
        ->name('session-attendances.destroy');
});

Route::middleware(['auth', 'permission:manage-evaluations'])->group(function () {
    Route::get('/evaluations', [EvaluationController::class, 'index'])
        ->name('evaluations.index');
    Route::post('/evaluations', [EvaluationController::class, 'store'])
        ->name('evaluations.store');

    Route::get('/evaluation-scores', [EvaluationScoreController::class, 'index'])
        ->name('evaluation-scores.index');
    Route::post('/evaluation-scores', [EvaluationScoreController::class, 'store'])
        ->name('evaluation-scores.store');
    Route::get('/evaluation-scores/{evaluationScore}/edit', [EvaluationScoreController::class, 'edit'])
        ->whereNumber('evaluationScore')
        ->name('evaluation-scores.edit');
    Route::put('/evaluation-scores/{evaluationScore}', [EvaluationScoreController::class, 'update'])
        ->whereNumber('evaluationScore')
        ->name('evaluation-scores.update');
    Route::delete('/evaluation-scores/{evaluationScore}', [EvaluationScoreController::class, 'destroy'])
        ->whereNumber('evaluationScore')
        ->name('evaluation-scores.destroy');
});

Route::middleware(['auth', 'permission:manage-training'])->group(function () {
    Route::get('/trainings', [TrainingController::class, 'index'])
        ->name('trainings.index');
    Route::post('/trainings', [TrainingController::class, 'store'])
        ->name('trainings.store');
});

Route::middleware(['auth', 'permission:manage-communication'])->group(function () {
    Route::get('/communications', [CommunicationController::class, 'index'])
        ->name('communications.index');
    Route::post('/communications', [CommunicationController::class, 'store'])
        ->name('communications.store');
});

Route::middleware(['auth', 'permission:manage-munaqosah'])->group(function () {
    Route::get('/munaqosahs', [MunaqosahController::class, 'index'])
        ->name('munaqosahs.index');
    Route::post('/munaqosahs', [MunaqosahController::class, 'store'])
        ->name('munaqosahs.store');
});

Route::middleware(['auth', 'permission:manage-report-cards'])->group(function () {
    Route::get('/report-cards', [ReportCardController::class, 'index'])
        ->name('report-cards.index');
    Route::post('/report-cards', [ReportCardController::class, 'store'])
        ->name('report-cards.store');
    Route::get('/report-cards/{reportCard}/edit', [ReportCardController::class, 'edit'])
        ->whereNumber('reportCard')
        ->name('report-cards.edit');
    Route::put('/report-cards/{reportCard}', [ReportCardController::class, 'update'])
        ->whereNumber('reportCard')
        ->name('report-cards.update');
    Route::delete('/report-cards/{reportCard}', [ReportCardController::class, 'destroy'])
        ->whereNumber('reportCard')
        ->name('report-cards.destroy');
});

Route::middleware(['auth', 'permission:manage-follow-ups'])->group(function () {
    Route::get('/follow-ups', [FollowUpController::class, 'index'])
        ->name('follow-ups.index');
    Route::post('/follow-ups', [FollowUpController::class, 'store'])
        ->name('follow-ups.store');
});

Route::middleware(['auth', 'permission:manage-progress-tracking'])->group(function () {
    Route::get('/progress-tracks', [ProgressTrackController::class, 'index'])
        ->name('progress-tracks.index');
    Route::post('/progress-tracks', [ProgressTrackController::class, 'store'])
        ->name('progress-tracks.store');
});

Route::middleware(['auth', 'permission:manage-milestones'])->group(function () {
    Route::get('/milestones', [MilestoneController::class, 'index'])
        ->name('milestones.index');
    Route::post('/milestones', [MilestoneController::class, 'store'])
        ->name('milestones.store');
});

Route::middleware(['auth', 'permission:manage-annual-audit'])->group(function () {
    Route::get('/annual-audits', [AnnualAuditController::class, 'index'])
        ->name('annual-audits.index');
    Route::post('/annual-audits', [AnnualAuditController::class, 'store'])
        ->name('annual-audits.store');
});

Route::middleware(['auth', 'permission:manage-organization-units'])->group(function () {
    Route::get('/organization-units', [OrganizationUnitController::class, 'index'])
        ->name('organization-units.index');
    Route::post('/organization-units', [OrganizationUnitController::class, 'store'])
        ->name('organization-units.store');
});

Route::middleware(['auth', 'permission:manage-assignments'])->group(function () {
    Route::get('/assignments', [AssignmentController::class, 'index'])
        ->name('assignments.index');
    Route::post('/assignments', [AssignmentController::class, 'store'])
        ->name('assignments.store');
});

Route::middleware(['auth', 'permission:manage-activity-schedules'])->group(function () {
    Route::get('/activity-schedules', [ActivityScheduleController::class, 'index'])
        ->name('activity-schedules.index');
    Route::post('/activity-schedules', [ActivityScheduleController::class, 'store'])
        ->name('activity-schedules.store');
});

Route::middleware(['auth', 'permission:manage-activity-executions'])->group(function () {
    Route::get('/activity-executions', [ActivityExecutionController::class, 'index'])
        ->name('activity-executions.index');
    Route::post('/activity-executions', [ActivityExecutionController::class, 'store'])
        ->name('activity-executions.store');
});
