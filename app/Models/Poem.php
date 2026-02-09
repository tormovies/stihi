<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poem extends Model
{
    protected $fillable = [
        'author_id', 'slug', 'title', 'body',
        'meta_title', 'meta_description', 'h1', 'h1_description',
        'published_at', 'likes',
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
}
