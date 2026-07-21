<?php

namespace App\Filters;

use App\Http\Controllers\Admin\SyaratController;
use App\Models\RefSyaratSurat;
use Illuminate\Database\Eloquent\Builder;

/**
 * SSOT logika filter index Master Persyaratan (ARCHITECTURE §17): search `nama`.
 *
 * @see SyaratController
 */
class SyaratFilter
{
    /**
     * @param  Builder<RefSyaratSurat>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<RefSyaratSurat>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $v): Builder => $q->where('nama', 'like', "%{$v}%"));
    }
}
