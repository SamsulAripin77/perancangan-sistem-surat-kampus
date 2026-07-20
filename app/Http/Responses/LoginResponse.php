<?php

namespace App\Http\Responses;

use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/**
 * Redirect setelah login (UX_SPEC 1.A.1 langkah 4): jika wajib ganti password
 * → halaman paksa ganti; selain itu → redirect by role.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        if ($user->must_change_password) {
            return redirect()->route('password.force');
        }

        return redirect()->to(AuthRedirect::url($user));
    }
}
