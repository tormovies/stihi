<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoemAnalysis extends Model
{
    protected $fillable = [
        'poem_id',
        'analysis_text',
        'meta_title',
        'meta_description',
        'h1',
        'h1_description',
    ];

    protected $appends = ['analysis_html'];

    /**
     * HTML для вывода на странице: если в БД уже HTML — возвращаем как есть, иначе конвертируем из Markdown (старые записи).
     */
    public function getAnalysisHtmlAttribute(): string
    {
        $text = $this->attributes['analysis_text'] ?? '';
        if ($text === '') {
            return '';
        }
        if (str_contains($text, '<')) {
            return $text;
        }
        return markdown_to_html($text);
    }

    public function poem(): BelongsTo
    {
        return $this->belongsTo(Poem::class);
    }
}
