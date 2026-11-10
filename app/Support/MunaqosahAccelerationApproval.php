<?php

namespace App\Support;

use App\Models\MunaqosahAccelerationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who may decide a pending munaqosah-akselerasi proposal: PPG Daerah (global
 * approve-munaqosah-acceleration), or a Perwakilan PPG Desa whose village matches the
 * generus's current village placement.
 */
class MunaqosahAccelerationApproval
{
    public static function canApprove(User $approver, MunaqosahAccelerationRequest $request): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        if ($approver->hasGlobalAccess(MunaqosahAccelerationRequest::APPROVE_PERMISSION)) {
            return true;
        }

        $villageIds = $approver->scopedPlacementIds(MunaqosahAccelerationRequest::APPROVE_PERMISSION)['village_id'] ?? [];

        if ($villageIds === []) {
            return false;
        }

        $villageId = $request->generus->assignments()->whereNull('ended_at')->latest('assigned_at')->first()?->village_id;

        return $villageId !== null && in_array($villageId, $villageIds, true);
    }

    /**
     * @return Builder<MunaqosahAccelerationRequest>
     */
    public static function pendingFor(User $approver): Builder
    {
        $query = MunaqosahAccelerationRequest::where('status', MunaqosahAccelerationRequest::STATUS_PENDING);

        if ($approver->hasGlobalAccess(MunaqosahAccelerationRequest::APPROVE_PERMISSION)) {
            return $query;
        }

        $villageIds = $approver->scopedPlacementIds(MunaqosahAccelerationRequest::APPROVE_PERMISSION)['village_id'] ?? [];

        if ($villageIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'generus.assignments',
            fn (Builder $assignments) => $assignments->whereNull('ended_at')->whereIn('village_id', $villageIds),
        );
    }
}
