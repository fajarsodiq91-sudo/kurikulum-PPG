<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\FaceDescriptors;
use App\Support\MemberLogin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = str_contains($validated['login'], '@') ? 'email' : 'username';

        if (Auth::attempt([$identifier => $validated['login'], 'password' => $validated['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'login' => 'Kredensial yang dimasukkan tidak valid.',
        ])->onlyInput('login');
    }

    /**
     * Sign in by tapping an RFID card. Reader input is untrusted, so failures never say why.
     */
    public function rfid(Request $request): JsonResponse
    {
        $validated = $request->validate(['uid' => ['required', 'string', 'max:50']]);

        return $this->signInMember($request, MemberLogin::forRfid($validated['uid']), 'Kartu tidak dikenali.');
    }

    /**
     * Sign in with a face descriptor the browser computed from the camera.
     */
    public function face(Request $request): JsonResponse
    {
        $validated = $request->validate(['descriptor' => ['required', FaceDescriptors::rule()]]);

        return $this->signInMember($request, MemberLogin::forFace(FaceDescriptors::parse($validated['descriptor'])), 'Wajah tidak dikenali.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function signInMember(Request $request, ?User $user, string $failureMessage): JsonResponse
    {
        if ($user === null) {
            return response()->json(['message' => $failureMessage], 422);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['redirect' => redirect()->intended('/dashboard')->getTargetUrl()]);
    }
}
