<?php

namespace App\Http\Controllers;

use App\Models\Dapukan;
use App\Models\Group;
use App\Models\User;
use App\Models\Village;
use App\Support\RegistrationApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationApprovalController extends Controller
{
    public function index(Request $request): View
    {
        return view('registrations.pending', [
            'users' => RegistrationApproval::pendingFor($request->user())->with('roles')->latest()->paginate(25),
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

        return redirect()->route('registrations.pending')->with('success', 'Pendaftaran berhasil disetujui.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless(RegistrationApproval::canApprove($request->user(), $user), 403);

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('registrations.pending')->with('success', 'Pendaftaran berhasil ditolak.');
    }
}
