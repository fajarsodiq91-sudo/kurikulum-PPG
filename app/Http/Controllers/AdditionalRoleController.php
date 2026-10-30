<?php

namespace App\Http\Controllers;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Notifications\AdditionalRoleSubmitted;
use App\Support\OrganizationRoleScopes;
use App\Support\RegistrationApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Lets an already-active member request one additional organisation role for themselves,
 * strictly below every role they already hold (daerah > desa > kelompok). The new role is
 * attached inactive and needs the same admin approval as a brand-new registration.
 */
class AdditionalRoleController extends Controller
{
    public function create(Request $request): View
    {
        $availableSlugs = $this->availableRoleSlugsFor($request->user());

        return view('profile.additional-role', [
            'roles' => Role::where('is_active', true)->whereIn('slug', $availableSlugs)->orderBy('name')->get(),
            'roleScopes' => OrganizationRoleScopes::SCOPES,
            'villages' => Village::where('is_active', true)->orderBy('name')->get(),
            'groups' => Group::with('village')->where('is_active', true)->orderBy('name')->get(),
            'dapukansByRole' => Dapukan::where('is_active', true)->orderBy('sort_order')->get()->groupBy('role_slug'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $availableSlugs = $this->availableRoleSlugsFor($user);

        $validated = $request->validate([
            'role_id' => ['required', Rule::exists('roles', 'id')->where('is_active', true)],
            'dapukan_id' => ['required', Rule::exists('dapukans', 'id')->where('is_active', true)],
            'village_id' => ['nullable', 'integer', 'exists:villages,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        if (! in_array($role->slug, $availableSlugs, true)) {
            throw ValidationException::withMessages(['role_id' => 'Peran ini tidak tersedia untuk diajukan dari peran Anda saat ini.']);
        }

        $scopeType = OrganizationRoleScopes::SCOPES[$role->slug];
        $dapukan = Dapukan::findOrFail($validated['dapukan_id']);

        if ($dapukan->role_slug !== $role->slug) {
            throw ValidationException::withMessages(['dapukan_id' => 'Dapukan yang dipilih tidak sesuai dengan peran.']);
        }

        if ($scopeType === 'village' && empty($validated['village_id'])) {
            throw ValidationException::withMessages(['village_id' => 'Pilih desa untuk peran Perwakilan Desa.']);
        }

        if ($scopeType === 'group') {
            if (empty($validated['village_id'])) {
                throw ValidationException::withMessages(['village_id' => 'Pilih desa untuk peran Pelaksana Kelompok.']);
            }

            if (empty($validated['group_id'])) {
                throw ValidationException::withMessages(['group_id' => 'Pilih kelompok untuk peran Pelaksana Kelompok.']);
            }

            $group = Group::findOrFail($validated['group_id']);

            if ($group->village_id !== (int) $validated['village_id']) {
                throw ValidationException::withMessages(['group_id' => 'Kelompok yang dipilih tidak sesuai dengan desa.']);
            }
        }

        $scopeId = match ($scopeType) {
            'village' => (int) $validated['village_id'],
            'group' => (int) $validated['group_id'],
            default => null,
        };

        $user->roles()->attach($role->id, [
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'dapukan_id' => $dapukan->id,
            'is_active' => false,
        ]);

        try {
            Notification::send(
                RegistrationApproval::approversForRole($role->slug, $scopeId),
                new AdditionalRoleSubmitted($user, $role)
            );
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi pengajuan peran tambahan ke approver.', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'exception' => $e,
            ]);
        }

        return redirect()->route('profile.edit')->with('success', 'Pengajuan peran tambahan telah terkirim dan menunggu persetujuan admin.');
    }

    /**
     * Role slugs the member may additionally request: strictly below every role they already
     * hold (active or still pending approval), and not already held or pending themselves.
     *
     * @return list<string>
     */
    private function availableRoleSlugsFor(User $user): array
    {
        $heldSlugs = $user->roles->pluck('slug')->all();
        $below = OrganizationRoleScopes::rolesBelowAll($heldSlugs);

        return array_values(array_diff($below, $heldSlugs));
    }
}
