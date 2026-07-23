<?php

namespace App\Filters;

use App\Http\Controllers\Admin\TemplateController;
use App\Models\Template;
use Illuminate\Database\Eloquent\Builder;

/**
 * SSOT logika filter index Template Surat (ARCHITECTURE §17): kategori, unit,
 * status, dan search nama.
 *
 * @see TemplateController
 */
class TemplateFilter
{
    /**
     * @param  Builder<Template>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Template>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, fn (Builder $q, string $v): Builder => $q->where('nama', 'like', "%{$v}%"))
            ->when($filters['kategori_id'] ?? null, fn (Builder $q, string $v): Builder => $q->where('kategori_id', $v))
            ->when($filters['unit_id'] ?? null, function (Builder $q, string $v): Builder {
                return $q->whereHas('units', fn (Builder $unit): Builder => $unit->whereKey($v));
            })
            ->when(
                ($filters['status'] ?? '') !== '',
                fn (Builder $q): Builder => $q->where('status', $filters['status'])
            );
    }
}
