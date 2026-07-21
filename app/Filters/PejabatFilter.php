<?php

namespace App\Filters;

use App\Http\Controllers\Admin\PejabatController;
use App\Models\Pejabat;
use Illuminate\Database\Eloquent\Builder;

/**
 * SSOT logika filter index Pejabat (ARCHITECTURE §17): search (nama/jabatan),
 * unit (via pivot pejabat_unit), dan status aktif.
 *
 * @see PejabatController
 */
class PejabatFilter
{
    /**
     * @param  Builder<Pejabat>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Pejabat>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $v): Builder => $q->where(
                fn (Builder $sub) => $sub->where('nama', 'like', "%{$v}%")->orWhere('jabatan', 'like', "%{$v}%")
            ))
            ->when($filters['unit'] ?? null, fn (Builder $q, string $v): Builder => $q->whereHas(
                'units', fn (Builder $u) => $u->where('units.id', $v)
            ))
            ->when(
                ($filters['status'] ?? '') !== '',
                fn (Builder $q): Builder => $q->where('is_active', (bool) $filters['status'])
            );
    }
}
