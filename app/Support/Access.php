<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class Access
{
    /**
     * Whether the current visitor (authenticated user or anonymous guest) has the given permission.
     */
    public static function can(string $permissionSlug): bool
    {
        if ($user = Auth::user()) {
            return $user->hasPermission($permissionSlug);
        }

        return self::guestRole()?->permissions->contains('slug', $permissionSlug) ?? false;
    }

    /**
     * Label describing the current visitor's access level, for display in the UI.
     */
    public static function currentRoleLabel(): string
    {
        if ($user = Auth::user()) {
            return $user->roles->first()?->name ?? 'Pengguna';
        }

        return self::guestRole()?->name ?? 'Tamu';
    }

    /**
     * The guest role, resolved once per request and cached on the container.
     */
    private static function guestRole(): ?Role
    {
        return once(function (): ?Role {
            return Role::query()
                ->where('slug', 'guest')
                ->where('is_active', true)
                ->with(['permissions' => fn ($query) => $query->where('is_active', true)])
                ->first();
        });
    }
}
