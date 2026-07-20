<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\PasswordUpdateResponse as PasswordUpdateResponseContract;

/**
 * Setelah ubah password (Fortify) → kembali ke halaman asal dengan flash sukses
 * yang ditampilkan sebagai SweetAlert (hook js-flash, ARCHITECTURE §11.4).
 */
class PasswordUpdateResponse implements PasswordUpdateResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->back()->with('success', __('auth.password_updated'));
    }
}
