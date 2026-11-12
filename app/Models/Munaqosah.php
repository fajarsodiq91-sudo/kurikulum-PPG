<?php

namespace App\Models;

use App\Support\GradePromotionService;
use App\Support\ReportCardScores;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A recorded munaqosah result. type=semester-final records from the semester-2 cycle feed
 * the rapor-publishing confirmation (App\Models\ReportCardGroupConfirmation) as a hint of
 * who the kelompok already flagged "tinggal" — kenaikan kelas for that cycle only actually
 * happens once PPG Daerah's rapor publication is confirmed (App\Support\GradePromotionService
 * is called from there, not from here). type=akselerasi records tied to an approved
 * MunaqosahAccelerationRequest are the exception: saving one with decision=naik promotes the
 * generus immediately (see promoteIfEligible()), since akselerasi happens outside the
 * semester cycle and has no publication gate to wait for.
 */
class Munaqosah extends Model
{
    public const MANAGE_PERMISSION = 'manage-munaqosah';

    public const TYPE_SEMESTER_FINAL = 'semester-final';

    public const TYPE_AKSELERASI = 'akselerasi';

    public const DECISION_NAIK = 'naik';

    public const DECISION_TINGGAL = 'tinggal';

    /**
     * The semester within an academic year whose munaqosah can decide a regular (non-
     * akselerasi) kenaikan kelas, matching Semester.sort_order after normalization.
     */
    public const PROMOTING_SEMESTER_SORT_ORDER = 2;

    protected $table = 'munaqosahs';

    protected $fillable = [
        'generus_id',
        'class_grade_id',
        'academic_year_id',
        'semester_id',
        'title',
        'type',
        'score',
        'result',
        'decision',
        'munaqosah_acceleration_request_id',
        'grade_scale_id',
        'grade_description',
        'status',
        'notes',
    ];

    protected $casts = [
        'promoted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $munaqosah): void {
            ReportCardScores::recalculate($munaqosah->generus_id, $munaqosah->academic_year_id, $munaqosah->semester_id);
            $munaqosah->promoteIfEligible();
        });
        static::deleted(fn (self $munaqosah) => ReportCardScores::recalculate($munaqosah->generus_id, $munaqosah->academic_year_id, $munaqosah->semester_id));
    }

    public function generus(): BelongsTo
    {
        return $this->belongsTo(Generus::class);
    }

    public function classGrade(): BelongsTo
    {
        return $this->belongsTo(ClassGrade::class);
    }

    public function gradeScale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function accelerationRequest(): BelongsTo
    {
        return $this->belongsTo(MunaqosahAccelerationRequest::class, 'munaqosah_acceleration_request_id');
    }

    /**
     * Whether saving this record may promote a generus immediately: only an akselerasi tied
     * to an approved request. A regular semester-final decision never promotes on its own —
     * that only happens once the semester's rapor publication is confirmed, so a kelompok
     * still gets one final chance to flag a "tinggal" before kenaikan kelas actually lands.
     */
    public function canDecidePromotion(): bool
    {
        return $this->type === self::TYPE_AKSELERASI && ($this->accelerationRequest?->isApproved() ?? false);
    }

    /**
     * Promotes the generus to the next kelas KBM when this record decided "naik" and hasn't
     * already acted (promoted_at guards against re-triggering on a later edit of the same
     * record, e.g. fixing a typo in notes).
     */
    public function promoteIfEligible(): void
    {
        if ($this->promoted_at !== null || $this->decision !== self::DECISION_NAIK || ! $this->canDecidePromotion()) {
            return;
        }

        $generus = $this->generus;
        $currentClassGrade = $this->classGrade ?? $generus?->learningClass;

        if ($generus === null || $currentClassGrade === null) {
            return;
        }

        GradePromotionService::promote($generus, GradePromotionService::nextClassGradeFor($currentClassGrade));

        $this->updateQuietly(['promoted_at' => now()]);
    }
}
