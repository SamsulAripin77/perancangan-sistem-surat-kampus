<?php

namespace App\Filters;

use App\Http\Controllers\Admin\KamusController;
use App\Models\PlaceholderDefinition;
use Illuminate\Database\Eloquent\Builder;

/**
 * SSOT logika filter index Kamus Placeholder (ARCHITECTURE §17): search `name`
 * + kelompok.
 *
 * @see KamusController
 */
class PlaceholderFilter
{
    /**
     * @param  Builder<PlaceholderDefinition>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<PlaceholderDefinition>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $v): Builder => $q->where('name', 'like', "%{$v}%"))
            ->when($filters['kelompok'] ?? null, fn (Builder $q, string $v): Builder => $q->where('kelompok', $v));
    }
}
