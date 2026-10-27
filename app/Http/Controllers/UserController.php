<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSheets;
use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Support\Sheets\UserSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    use HandlesSheets;

    /**
     * Organisation roles whose scope is fixed by what the role means (PPG is daerah-wide,
     * perwakilan-ppg-desa is one desa, pelaksana-ppg-kelompok is one kelompok): the admin form
     * cannot override these, so a mismatched or missing submission falls back to this value.
     *
     * @var array<string, string>
     */
    private const FIXED_ROLE_SCOPES = [
        'ppg' => 'global',
        'perwakilan-ppg-desa' => 'village',
        'pelaksana-ppg-kelompok' => 'group',
    ];

    public function index(): View
    {
        return view('users.index', [
            'users' => User::with('roles')->latest()->paginate(25),
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
            ]);

            $this->syncRoles($request, $user, $validated['roles'] ?? []);
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
            'fixedRoleScopes' => self::FIXED_ROLE_SCOPES,
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
            $fixedType = self::FIXED_ROLE_SCOPES[$roleSlug ?? ''] ?? null;
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
        if (! array_key_exists($roleSlug, self::FIXED_ROLE_SCOPES)) {
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
