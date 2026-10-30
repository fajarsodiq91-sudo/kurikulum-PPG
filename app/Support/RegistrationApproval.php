<?php

namespace App\Support;

use App\Models\Dapukan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Who may approve a pending self-registration, following the organisation hierarchy: a
 * superadmin approves everyone; a PH PPG dapukan holder approves every pending Daerah (ppg)
 * registration; a Perwakilan Desa or Pelaksana Kelompok approves any pending registration
 * sharing their own desa/kelompok, regardless of the applicant's own dapukan.
 */
class RegistrationApproval
{
    /**
     * @return Builder<User>
     */
    public static function pendingFor(User $approver): Builder
    {
        $query = User::query()->where('status', 'pending');

        if ($approver->hasGlobalAccess('manage-users')) {
            return $query;
        }

        $scopes = self::approvableRoleScopes($approver);

        if ($scopes === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($scopes): void {
            foreach ($scopes as [$roleSlug, $scopeId]) {
                $outer->orWhereHas('roles', function (Builder $roles) use ($roleSlug, $scopeId): void {
                    $roles->where('roles.slug', $roleSlug);

                    if ($scopeId !== null) {
                        $roles->where('user_roles.scope_id', $scopeId);
                    }
                });
            }
        });
    }

    /**
     * Every active user who may approve this pending registration, to notify about it.
     *
     * @return EloquentCollection<int, User>
     */
    public static function approversFor(User $pending): EloquentCollection
    {
        return User::where('status', 'active')
            ->with('roles.permissions')
            ->get()
            ->filter(fn (User $approver): bool => self::canApprove($approver, $pending))
            ->values();
    }

    public static function canApprove(User $approver, User $pending): bool
    {
        if ($pending->status !== 'pending') {
            return false;
        }

        if ($approver->hasGlobalAccess('manage-users')) {
            return true;
        }

        $pendingRole = $pending->roles->first();

        if ($pendingRole === null) {
            return false;
        }

        foreach (self::approvableRoleScopes($approver) as [$roleSlug, $scopeId]) {
            if ($roleSlug === $pendingRole->slug && ($scopeId === null || $scopeId === $pendingRole->pivot->scope_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Active members (not a brand-new pending account) with one or more additional roles they
     * requested for themselves, still awaiting approval (pivot is_active = false). Scoped the
     * same way as pendingFor().
     *
     * @return Builder<User>
     */
    public static function pendingAdditionalRolesFor(User $approver): Builder
    {
        $query = User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn (Builder $q) => $q->where('user_roles.is_active', false));

        if ($approver->hasGlobalAccess('manage-users')) {
            return $query;
        }

        $scopes = self::approvableRoleScopes($approver);

        if ($scopes === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $outer) use ($scopes): void {
            foreach ($scopes as [$roleSlug, $scopeId]) {
                $outer->orWhereHas('roles', function (Builder $roles) use ($roleSlug, $scopeId): void {
                    $roles->where('roles.slug', $roleSlug)->where('user_roles.is_active', false);

                    if ($scopeId !== null) {
                        $roles->where('user_roles.scope_id', $scopeId);
                    }
                });
            }
        });
    }

    /**
     * May $approver approve $member's inactive $role (an additional role an already-active
     * member requested for themselves)?
     */
    public static function canApproveAdditionalRole(User $approver, User $member, Role $role): bool
    {
        if ($member->status !== 'active') {
            return false;
        }

        $pivot = $member->roles->firstWhere('id', $role->id)?->pivot;

        if ($pivot === null || (bool) $pivot->is_active) {
            return false;
        }

        if ($approver->hasGlobalAccess('manage-users')) {
            return true;
        }

        foreach (self::approvableRoleScopes($approver) as [$roleSlug, $scopeId]) {
            if ($roleSlug === $role->slug && ($scopeId === null || $scopeId === $pivot->scope_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every active user who may approve a pending additional-role request for the given role
     * slug and scope, to notify about it. Used instead of approversFor() because, at the point
     * the request is submitted, the pivot row and the User's loaded roles aren't a convenient
     * single object to pass around.
     *
     * @return EloquentCollection<int, User>
     */
    public static function approversForRole(string $roleSlug, ?int $scopeId): EloquentCollection
    {
        return User::where('status', 'active')
            ->with('roles.permissions')
            ->get()
            ->filter(function (User $approver) use ($roleSlug, $scopeId): bool {
                if ($approver->hasGlobalAccess('manage-users')) {
                    return true;
                }

                foreach (self::approvableRoleScopes($approver) as [$slug, $approverScopeId]) {
                    if ($slug === $roleSlug && ($approverScopeId === null || $approverScopeId === $scopeId)) {
                        return true;
                    }
                }

                return false;
            })
            ->values();
    }

    /**
     * This approver's own active organisation memberships, translated into (role slug, scope id)
     * pairs they may approve pending registrations for. A null scope id means "any" (used only
     * for a PH PPG holder approving Daerah-wide).
     *
     * @return list<array{0: string, 1: int|null}>
     */
    private static function approvableRoleScopes(User $approver): array
    {
        $phPpgIds = Dapukan::where('role_slug', 'ppg')->where('can_approve', true)->pluck('id');

        return $approver->roles
            ->filter(fn (Role $role): bool => (bool) $role->pivot->is_active)
            ->flatMap(function (Role $role) use ($phPpgIds): array {
                if ($role->slug === 'ppg' && $phPpgIds->contains($role->pivot->dapukan_id)) {
                    return [['ppg', null]];
                }

                if (in_array($role->slug, ['perwakilan-ppg-desa', 'pelaksana-ppg-kelompok'], true) && $role->pivot->scope_id !== null) {
                    return [[$role->slug, (int) $role->pivot->scope_id]];
                }

                return [];
            })
            ->values()
            ->all();
    }
}
