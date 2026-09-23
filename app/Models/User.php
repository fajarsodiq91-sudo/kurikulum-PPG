<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['scope_type', 'scope_id', 'is_active'])
            ->withTimestamps();
    }

    public function assignRole(int $roleId, string $scopeType = 'global', ?int $scopeId = null): void
    {
        $this->roles()->syncWithoutDetaching([
            $roleId => [
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
                'is_active' => true,
            ],
        ]);
    }

    /**
     * Placement column checked for each role scope type. Any other scope type grants nothing.
     *
     * @var array<string, string>
     */
    private const SCOPE_COLUMNS = [
        'region' => 'region_id',
        'village' => 'village_id',
        'group' => 'group_id',
    ];

    /**
     * @var array<string, Collection<int, array{type: string, id: int|null}>>
     */
    private array $permissionScopeCache = [];

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->rolesGrantingPermission($permissionSlug)->exists();
    }

    /**
     * Scopes (from the `user_roles` pivot) of every active role granting the permission.
     *
     * @return Collection<int, array{type: string, id: int|null}>
     */
    public function permissionScopes(string $permissionSlug): Collection
    {
        if ($this->status !== 'active') {
            return collect();
        }

        return $this->permissionScopeCache[$permissionSlug] ??= $this->rolesGrantingPermission($permissionSlug)
            ->get()
            ->map(fn (Role $role): array => [
                'type' => $role->pivot->scope_type,
                'id' => $role->pivot->scope_id,
            ])
            ->values();
    }

    public function hasGlobalAccess(string $permissionSlug): bool
    {
        return $this->permissionScopes($permissionSlug)->contains('type', 'global');
    }

    /**
     * Placement-column constraints for a scoped user, e.g. `['group_id' => [3, 7]]`.
     *
     * @return array<string, list<int>>
     */
    public function scopedPlacementIds(string $permissionSlug): array
    {
        return $this->permissionScopes($permissionSlug)
            ->filter(fn (array $scope): bool => isset(self::SCOPE_COLUMNS[$scope['type']]) && $scope['id'] !== null)
            ->groupBy(fn (array $scope): string => self::SCOPE_COLUMNS[$scope['type']])
            ->map(fn (Collection $scopes): array => $scopes->pluck('id')->map(fn (mixed $id): int => (int) $id)->all())
            ->all();
    }

    public function coversPlacement(string $permissionSlug, ?int $regionId, ?int $villageId, ?int $groupId): bool
    {
        if ($this->hasGlobalAccess($permissionSlug)) {
            return true;
        }

        $placement = ['region_id' => $regionId, 'village_id' => $villageId, 'group_id' => $groupId];

        return collect($this->scopedPlacementIds($permissionSlug))
            ->contains(fn (array $ids, string $column): bool => $placement[$column] !== null && in_array($placement[$column], $ids, true));
    }

    private function rolesGrantingPermission(string $permissionSlug): BelongsToMany
    {
        return $this->roles()
            ->whereNull('roles.deleted_at')
            ->where('roles.is_active', true)
            ->where('user_roles.is_active', true)
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('permissions.slug', $permissionSlug)
                    ->whereNull('permissions.deleted_at')
                    ->where('permissions.is_active', true);
            });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
