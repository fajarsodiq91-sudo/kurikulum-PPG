<?php

namespace App\Http\Middleware;

use App\Support\Access;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Authenticated users without any of the listed permissions are forbidden. Anonymous
     * visitors are granted read-only access when the guest role covers one of the
     * permissions, otherwise they are sent to log in to obtain the appropriate role
     * permission. Listing a view-only permission alongside its manage counterpart lets
     * both guests and full-access staff reach the same read-only route.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if ($user = $request->user()) {
            if (! collect($permissions)->contains(fn (string $permission): bool => $user->hasPermission($permission))) {
                abort(403, 'Forbidden');
            }

            return $next($request);
        }

        if (! Access::can(...$permissions)) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
