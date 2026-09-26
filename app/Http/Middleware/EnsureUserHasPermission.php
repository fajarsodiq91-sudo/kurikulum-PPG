<?php

namespace App\Http\Middleware;

use App\Support\Access;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Authenticated users without the permission are forbidden. Anonymous visitors are
     * granted read-only access when the guest role covers the permission, otherwise they
     * are sent to log in to obtain the appropriate role permission.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if ($user = $request->user()) {
            if (! $user->hasPermission($permission)) {
                abort(403, 'Forbidden');
            }

            return $next($request);
        }

        if (! Access::can($permission)) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
