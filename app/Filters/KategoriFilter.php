<?php

namespace App\Filters;

use App\Http\Controllers\Admin\KategoriController;
use App\Models\KategoriSurat;
use Illuminate\Database\Eloquent\Builder;

/**
 * SSOT logika filter index Kategori Surat (ARCHITECTURE §17): search `nama`
 * + status aktif.
 *
 * @see KategoriController
 */
class KategoriFilter
{
    /**
     * @param  Builder<KategoriSurat>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<KategoriSurat>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $v): Builder => $q->where('nama', 'like', "%{$v}%"))
            ->when(
                ($filters['status'] ?? '') !== '',
                fn (Builder $q): Builder => $q->where('is_active', (bool) $filters['status'])
            );
    }
}
