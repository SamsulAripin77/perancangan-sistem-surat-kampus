<?php

namespace App\Filters;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

/**
 * SSOT logika filter index Unit (ARCHITECTURE §17): global search (nama/kode)
 * + status aktif. Dipakai UnitController pada query Yajra.
 */
class UnitFilter
{
    /**
     * @param  Builder<Unit>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Unit>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $v): Builder => $q->where(
                fn (Builder $sub) => $sub->where('nama', 'like', "%{$v}%")->orWhere('kode', 'like', "%{$v}%")
            ))
            ->when(
                ($filters['status'] ?? '') !== '',
                fn (Builder $q): Builder => $q->where('is_active', (bool) $filters['status'])
            );
    }
}
