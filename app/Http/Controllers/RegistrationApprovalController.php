<?php

namespace App\Http\Controllers;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Notifications\AdditionalRoleDecided;
use App\Notifications\RegistrationDecided;
use App\Support\RegistrationApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $approver = $request->user();

        return view('registrations.pending', [
            'users' => RegistrationApproval::pendingFor($approver)->with('roles')->latest()->paginate(25, ['*'], 'page'),
            'additionalRoleUsers' => RegistrationApproval::pendingAdditionalRolesFor($approver)->with('roles')->latest()->paginate(25, ['*'], 'role_page'),
            'dapukans' => Dapukan::pluck('name', 'id'),
            'villages' => Village::pluck('name', 'id'),
            'groups' => Group::with('village')->get()->keyBy('id'),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless(RegistrationApproval::canApprove($request->user(), $user), 403);

        $role = $user->roles->first();

        $user->update(['status' => 'active']);
        $user->roles()->updateExistingPivot($role->id, ['is_active' => true]);

        $user->notify(new RegistrationDecided(approved: true));

        return redirect()->route('registrations.pending')->with('success', 'Pendaftaran berhasil disetujui.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless(RegistrationApproval::canApprove($request->user(), $user), 403);

        $user->notify(new RegistrationDecided(approved: false));

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('registrations.pending')->with('success', 'Pendaftaran berhasil ditolak.');
    }

    /**
     * Approve an additional role an already-active member requested for themselves. Unlike
     * approve(), the member's account and other roles are untouched - only this one pivot
     * row flips active.
     */
    public function approveRole(Request $request, User $user, Role $role): RedirectResponse
    {
        abort_unless(RegistrationApproval::canApproveAdditionalRole($request->user(), $user, $role), 403);

        $user->roles()->updateExistingPivot($role->id, ['is_active' => true]);

        $user->notify(new AdditionalRoleDecided(approved: true, role: $role));

        return redirect()->route('registrations.pending')->with('success', 'Pengajuan peran tambahan berhasil disetujui.');
    }

    /**
     * Reject an additional role an already-active member requested for themselves. Unlike
     * reject(), only this one role is detached - the member's account is never deleted.
     */
    public function rejectRole(Request $request, User $user, Role $role): RedirectResponse
    {
        abort_unless(RegistrationApproval::canApproveAdditionalRole($request->user(), $user, $role), 403);

        $user->notify(new AdditionalRoleDecided(approved: false, role: $role));

        $user->roles()->detach($role->id);

        return redirect()->route('registrations.pending')->with('success', 'Pengajuan peran tambahan ditolak.');
    }
}
