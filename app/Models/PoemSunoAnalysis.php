<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoemSunoAnalysis extends Model
{
    public const STATUS_SUPER = 'super';
    public const STATUS_STRONG = 'strong';
    public const STATUS_MEDIUM = 'medium';
    public const STATUS_WEAK = 'weak';

    protected $fillable = [
        'poem_id',
        'score_hook', 'score_rhythm', 'score_dynamics', 'score_plot', 'score_vocal_air', 'score_total',
        'status', 'suitable_for_suno',
        'male_fit', 'male_verdict', 'male_why',
        'folk_fit', 'folk_verdict', 'folk_why',
        'comfort_fit', 'comfort_verdict', 'comfort_why',
        'marked_lyrics', 'styles',
        'best_overall', 'best_viral', 'best_cult',
        'structure_notes', 'risks', 'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'suitable_for_suno' => 'boolean',
            'styles' => 'array',
            'risks' => 'array',
        ];
    }

    public function poem(): BelongsTo
    {
        return $this->belongsTo(Poem::class);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUPER => 'Супер-сильный',
            self::STATUS_STRONG => 'Сильный',
            self::STATUS_MEDIUM => 'Средний',
            self::STATUS_WEAK => 'Слабый',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public static function verdictOptions(): array
    {
        return [
            'yes' => 'да',
            'maybe' => 'хз',
            'no' => 'нет',
        ];
    }

    public function flames(int $n): string
    {
        $n = max(0, min(5, $n));

        return $n > 0 ? str_repeat('🔥', $n) : '—';
    }
}
