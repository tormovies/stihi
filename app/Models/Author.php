<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'years_of_life',
        'sort_order',
        'meta_title',
        'meta_description',
        'h1',
        'h1_description',
    ];

    public function poems(): HasMany
    {
        return $this->hasMany(Poem::class);
    }

    public function publishedPoems(): HasMany
    {
        return $this->hasMany(Poem::class)->whereNotNull('published_at');
    }

    /**
     * Год смерти из years_of_life (например «1799–1837» → 1837).
     * Если указан только один год — не считаем годом смерти.
     */
    public function deathYear(): ?int
    {
        $raw = trim((string) ($this->years_of_life ?? ''));
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace(['–', '—', '−'], '-', $raw);
        if (!preg_match_all('/\b((?:1[0-9]{3})|(?:20[0-9]{2}))\b/', $normalized, $matches)) {
            return null;
        }

        $years = array_map('intval', $matches[1]);
        if (count($years) < 2) {
            return null;
        }

        return max($years);
    }

    /** Умер менее $years лет назад (по years_of_life). */
    public function diedLessThanYearsAgo(int $years = 75): bool
    {
        $deathYear = $this->deathYear();
        if ($deathYear === null) {
            return false;
        }

        return ((int) now()->year - $deathYear) < $years;
    }
}
