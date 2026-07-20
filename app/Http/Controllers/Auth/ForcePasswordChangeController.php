<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Halaman paksa ganti password login pertama (UX_SPEC 1.A.1). Tanpa password
 * lama — hanya password baru + konfirmasi. Setelah sukses, must_change_password
 * di-set false lalu redirect by role.
 */
class ForcePasswordChangeController extends Controller
{
    public function show(Request $request): RedirectResponse|View
    {
        if (! $request->user()->must_change_password) {
            return redirect()->to(AuthRedirect::url($request->user()));
        }

        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ])->save();

        return redirect()->to(AuthRedirect::url($user));
    }
}
