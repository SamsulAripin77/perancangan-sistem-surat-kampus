<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Konfigurasi placeholder hasil scan untuk satu template (ERD §9).
 */
class TemplatePlaceholderConfig extends Model
{
    public $timestamps = false;

    protected $table = 'template_placeholder_config';

    protected $fillable = [
        'template_id',
        'placeholder_name',
        'label_mahasiswa',
        'tipe_input',
        'filled_by',
        'is_required',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
