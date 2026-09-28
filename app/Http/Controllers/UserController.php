<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);

        $this->syncRoles($request, $user, $validated['roles'] ?? []);

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
        $sync = [];

        foreach (array_filter($roleIds) as $roleId) {
            $type = $request->input("role_scopes.{$roleId}.type");
            $scopeId = $request->input("role_scopes.{$roleId}.id");

            if (in_array($type, ['village', 'group'], true)) {
                $exists = $type === 'village' ? Village::whereKey($scopeId)->exists() : Group::whereKey($scopeId)->exists();

                if (! $exists) {
                    throw ValidationException::withMessages(['role_scopes' => 'Pilih '.($type === 'village' ? 'desa' : 'kelompok').' untuk cakupan peran yang dipilih.']);
                }

                $sync[$roleId] = ['scope_type' => $type, 'scope_id' => (int) $scopeId, 'is_active' => true];
            } elseif ($type === 'global' || ! $existing->has((int) $roleId)) {
                $sync[$roleId] = ['scope_type' => 'global', 'scope_id' => null, 'is_active' => true];
            } else {
                $pivot = $existing->get((int) $roleId)->pivot;
                $sync[$roleId] = ['scope_type' => $pivot->scope_type, 'scope_id' => $pivot->scope_id, 'is_active' => (bool) $pivot->is_active];
            }
        }

        $user->roles()->sync($sync);
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
            'status' => ['required', 'in:active,inactive'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['nullable', 'integer', 'exists:roles,id'],
        ]);
    }
}
