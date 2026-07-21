<?php

namespace App\Models;

use Database\Factories\PlaceholderDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kamus master placeholder yang dikenal sistem (ERD §8, PRD F13). Di-seed saat
 * instalasi, dikelola Super Admin (M1-T9). Dicocokkan exact-match `name` saat
 * scan template — bukan FK fisik.
 */
class PlaceholderDefinition extends Model
{
    /** @use HasFactory<PlaceholderDefinitionFactory> */
    use HasFactory;

    /** Kelompok placeholder (ERD §8). `ttd` tak di-seed (deteksi via regex). */
    public const KELOMPOK = ['profil', 'waktu', 'sistem', 'counter', 'ttd'];

    /** Tipe input yang dikenal untuk render form generate. */
    public const INPUT_TYPES = ['text', 'date', 'image', 'number'];

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
