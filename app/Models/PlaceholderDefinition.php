<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kamus master placeholder yang dikenal sistem (ERD §8, PRD F13). Di-seed saat
 * instalasi, dikelola Super Admin (M1-T9). Dicocokkan exact-match `name` saat
 * scan template — bukan FK fisik.
 */
class PlaceholderDefinition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'kelompok',
        'input_type',
        'source',
        'is_overridable',
    ];

    protected function casts(): array
    {
        return [
            'is_overridable' => 'boolean',
        ];
    }
}
