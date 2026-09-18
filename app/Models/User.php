<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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

    public function roles()
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

    public function hasPermission(string $permissionSlug): bool
    {
        $permission = Permission::query()
            ->where('slug', $permissionSlug)
            ->where('is_active', true)
            ->first();

        if (! $permission) {
            return false;
        }

        return $this->roles()
            ->whereNull('roles.deleted_at')
            ->where('roles.is_active', true)
            ->where('user_roles.is_active', true)
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('permissions.id', $permission->id)
                    ->whereNull('permissions.deleted_at')
                    ->where('permissions.is_active', true);
            })
            ->exists();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
