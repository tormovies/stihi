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
}
