<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Poem extends Model
{
    public const SONG_STATUS_NONE = 'none';
    public const SONG_STATUS_HAS = 'has';
    public const SONG_STATUS_SELECTED = 'selected';
    public const SONG_STATUS_NOT_SUITABLE = 'not_suitable';

    protected $fillable = [
        'author_id', 'slug', 'title', 'body', 'body_length',
        'meta_title', 'meta_description', 'h1', 'h1_description',
        'published_at', 'likes', 'song_status', 'song_url',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(PoemAnalysis::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'poem_tag');
    }

    public static function songStatusOptions(): array
    {
        return [
            self::SONG_STATUS_NONE => 'Нет',
            self::SONG_STATUS_HAS => 'Есть',
            self::SONG_STATUS_SELECTED => 'Выбран',
            self::SONG_STATUS_NOT_SUITABLE => 'Не подходит',
        ];
    }
}
