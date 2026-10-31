<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Notifications\UserAccountCreated;
use App\Support\OrganizationRoleScopes;
use App\Support\Sheets\UserSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class UserController extends Controller
{
    use HandlesSheets;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');
        $role = $request->query('role', '');

        $users = User::with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($role !== '', fn ($query) => $query->whereHas('roles', fn ($query) => $query->where('slug', $role)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(),
            ...$this->scopeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        DB::transaction(function () use ($request, $validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
                'whatsapp' => $validated['whatsapp'] ?? null,
            ]);

            $this->syncRoles($request, $user, $validated['roles'] ?? []);

            try {
                $user->notify(new UserAccountCreated($validated['password']));
            } catch (Throwable $e) {
                Log::error('Gagal mengirim email akun pengguna baru.', [
                    'user_id' => $user->id,
                    'exception' => $e,
                ]);
            }
        });

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(),
            ...$this->scopeOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateUser($request, $user);

        DB::transaction(function () use ($request, $user, $validated): void {
            $payload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'status' => $validated['status'],
                'whatsapp' => $validated['whatsapp'] ?? null,
            ];

            if ($request->filled('password')) {
                $payload['password'] = Hash::make($validated['password']);
            }

            $user->update($payload);
            $this->syncRoles($request, $user, $validated['roles'] ?? []);
        });

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors([
                'user' => 'Anda tidak dapat menghapus akun yang sedang digunakan saat ini.',
            ]);
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function scopeOptions(): array
    {
        return [
            'villages' => Village::where('is_active', true)->orderBy('name')->get(),
            'groups' => Group::with('village')->where('is_active', true)->orderBy('name')->get(),
            'fixedRoleScopes' => OrganizationRoleScopes::SCOPES,
            'dapukansByRole' => Dapukan::where('is_active', true)->orderBy('sort_order')->get()->groupBy('role_slug'),
        ];
    }

    /**
     * Each checked role carries its own scope: global, one village or one group. A role without
     * a submitted scope keeps the scope it already had (e.g. a teacher account's own scope), or
     * becomes global for a newly added role.
     *
     * @param  list<int|string|null>  $roleIds
     */
    private function syncRoles(Request $request, User $user, array $roleIds): void
    {
        $existing = $user->roles()->get()->keyBy('id');
        $roleSlugs = Role::whereIn('id', array_filter($roleIds))->pluck('slug', 'id');
        $sync = [];

        foreach (array_filter($roleIds) as $roleId) {
            $roleSlug = $roleSlugs[$roleId] ?? null;
            $fixedType = OrganizationRoleScopes::SCOPES[$roleSlug ?? ''] ?? null;
            $type = $fixedType ?? $request->input("role_scopes.{$roleId}.type");
            $scopeId = $request->input("role_scopes.{$roleId}.id");
            $dapukanId = $roleSlug !== null ? $this->resolveDapukanId($request, (int) $roleId, $roleSlug) : null;

            if (in_array($type, ['village', 'group'], true)) {
                $exists = $type === 'village' ? Village::whereKey($scopeId)->exists() : Group::whereKey($scopeId)->exists();

                if (! $exists) {
                    throw ValidationException::withMessages(['role_scopes' => 'Pilih '.($type === 'village' ? 'desa' : 'kelompok').' untuk cakupan peran yang dipilih.']);
                }

                $sync[$roleId] = ['scope_type' => $type, 'scope_id' => (int) $scopeId, 'dapukan_id' => $dapukanId, 'is_active' => true];
            } elseif ($type === 'global' || ! $existing->has((int) $roleId)) {
                $sync[$roleId] = ['scope_type' => 'global', 'scope_id' => null, 'dapukan_id' => $dapukanId, 'is_active' => true];
            } else {
                $pivot = $existing->get((int) $roleId)->pivot;
                $sync[$roleId] = ['scope_type' => $pivot->scope_type, 'scope_id' => $pivot->scope_id, 'dapukan_id' => $dapukanId ?? $pivot->dapukan_id, 'is_active' => (bool) $pivot->is_active];
            }
        }

        $user->roles()->sync($sync);
    }

    /**
     * Only the three fixed-scope organisation roles carry a dapukan; any other role ignores it.
     */
    private function resolveDapukanId(Request $request, int $roleId, string $roleSlug): ?int
    {
        if (! array_key_exists($roleSlug, OrganizationRoleScopes::SCOPES)) {
            return null;
        }

        $dapukanId = $request->input("role_dapukan.{$roleId}");

        if (! Dapukan::where('id', $dapukanId)->where('role_slug', $roleSlug)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['role_dapukan' => 'Pilih dapukan untuk peran yang dipilih.']);
        }

        return (int) $dapukanId;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [
                $user ? 'nullable' : 'required',
                'string',
                'min:8',
            ],
            'status' => ['required', 'in:active,inactive,pending'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['nullable', 'integer', 'exists:roles,id'],
        ]);
    }

    public function export(): BinaryFileResponse
    {
        return $this->exportSheet(new UserSheet);
    }

    public function import(Request $request): RedirectResponse
    {
        return $this->importSheet($request, new UserSheet);
    }
}
