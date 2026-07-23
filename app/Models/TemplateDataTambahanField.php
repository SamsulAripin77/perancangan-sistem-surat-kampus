<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Definisi field data tambahan per template (ERD §13).
 */
class TemplateDataTambahanField extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'template_id',
        'label',
        'field_key',
        'tipe_input',
        'is_required',
        'helper_text',
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
