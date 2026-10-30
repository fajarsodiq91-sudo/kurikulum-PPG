<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dapukan;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Models\Village;
use App\Notifications\RegistrationSubmitted;
use App\Support\RegistrationApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegistrationController extends Controller
{
    /**
     * The only roles a Google account may request for itself; each carries a fixed scope.
     *
     * @var array<string, string>
     */
    private const ROLE_SCOPES = [
        'ppg' => 'global',
        'perwakilan-ppg-desa' => 'village',
        'pelaksana-ppg-kelompok' => 'group',
    ];

    public function create(Request $request): View|RedirectResponse
    {
        $registration = $request->session()->get('google_registration');

        if (! $registration) {
            return redirect()->route('login');
        }

        return view('auth.register', [
            'registration' => $registration,
            'roles' => Role::where('is_active', true)->whereIn('slug', array_keys(self::ROLE_SCOPES))->orderBy('name')->get(),
            'roleScopes' => self::ROLE_SCOPES,
            'villages' => Village::where('is_active', true)->orderBy('name')->get(),
            'groups' => Group::with('village')->where('is_active', true)->orderBy('name')->get(),
            'dapukansByRole' => Dapukan::where('is_active', true)->orderBy('sort_order')->get()->groupBy('role_slug'),
        ]);
    }

    /**
     * Create a pending account with the requested role attached but inactive, so an admin
     * with manage-users must approve it (via the normal user-edit form) before it grants anything.
     */
    public function store(Request $request): RedirectResponse
    {
        $registration = $request->session()->get('google_registration');

        if (! $registration) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('is_active', true)],
            'dapukan_id' => ['required', Rule::exists('dapukans', 'id')->where('is_active', true)],
            'village_id' => ['nullable', 'integer', 'exists:villages,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $scopeType = self::ROLE_SCOPES[$role->slug] ?? null;

        if ($scopeType === null) {
            throw ValidationException::withMessages(['role_id' => 'Peran tidak tersedia untuk pendaftaran mandiri.']);
        }

        $dapukan = Dapukan::findOrFail($validated['dapukan_id']);

        if ($dapukan->role_slug !== $role->slug) {
            throw ValidationException::withMessages(['dapukan_id' => 'Dapukan yang dipilih tidak sesuai dengan peran.']);
        }

        if ($scopeType === 'village' && empty($validated['village_id'])) {
            throw ValidationException::withMessages(['village_id' => 'Pilih desa untuk peran Perwakilan Desa.']);
        }

        if ($scopeType === 'group' && empty($validated['group_id'])) {
            throw ValidationException::withMessages(['group_id' => 'Pilih kelompok untuk peran Pelaksana Kelompok.']);
        }

        $scopeId = match ($scopeType) {
            'village' => (int) $validated['village_id'],
            'group' => (int) $validated['group_id'],
            default => null,
        };

        $user = User::create([
            'name' => $validated['name'],
            'email' => $registration['email'],
            'status' => 'pending',
        ]);

        $user->roles()->attach($role->id, [
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'dapukan_id' => $dapukan->id,
            'is_active' => false,
        ]);

        $request->session()->forget('google_registration');

        $user->load('roles');

        try {
            Notification::send(RegistrationApproval::approversFor($user), new RegistrationSubmitted($user));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim notifikasi pendaftaran baru ke approver.', [
                'user_id' => $user->id,
                'exception' => $e,
            ]);
        }

        return redirect()->route('login')->with('success', 'Pendaftaran Anda telah terkirim dan menunggu persetujuan admin.');
    }
}
