<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * User dengan must_change_password=true tidak boleh mengakses halaman lain
 * sebelum mengganti password (UX_SPEC 1.A.1, BR M1-T2). Rute halaman paksa
 * ganti password & logout dikecualikan agar tidak infinite-redirect.
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->must_change_password
            && ! $request->routeIs('password.force', 'password.force.update', 'logout')
        ) {
            return redirect()->route('password.force');
        }

        return $next($request);
    }
}
