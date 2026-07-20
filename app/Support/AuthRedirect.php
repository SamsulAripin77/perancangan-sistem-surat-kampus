<?php

namespace App\Support;

use App\Models\User;

/**
 * SSOT tujuan redirect setelah login sesuai role (UX_SPEC 1.A.1 langkah 5):
 * super_admin/admin_surat → admin.dashboard, mahasiswa → mahasiswa.beranda.
 */
class AuthRedirect
{
    public static function routeName(User $user): string
    {
        return $user->hasRole('mahasiswa')
            ? 'mahasiswa.beranda'
            : 'admin.dashboard';
    }

    public static function url(User $user): string
    {
        return route(self::routeName($user));
    }
}
