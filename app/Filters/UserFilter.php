<?php

namespace App\Filters;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * SSOT logika filter index User (ARCHITECTURE §17): search (nama/email/NIM),
 * role (Spatie), unit, dan status aktif.
 *
 * @see UserController
 */
class UserFilter
{
    /**
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<User>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $v): Builder => $q->where(
                fn (Builder $sub) => $sub->where('name', 'like', "%{$v}%")
                    ->orWhere('email', 'like', "%{$v}%")
                    ->orWhereHas('mahasiswa', fn (Builder $m) => $m->where('nim', 'like', "%{$v}%"))
            ))
            ->when($filters['role'] ?? null, fn (Builder $q, string $v): Builder => $q->whereHas(
                'roles', fn (Builder $r) => $r->where('name', $v)
            ))
            ->when($filters['unit'] ?? null, fn (Builder $q, string $v): Builder => $q->where('unit_id', $v))
            ->when(
                ($filters['status'] ?? '') !== '',
                fn (Builder $q): Builder => $q->where('is_active', (bool) $filters['status'])
            );
    }
}
