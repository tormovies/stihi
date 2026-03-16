<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'h1',
        'h1_description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function poems(): BelongsToMany
    {
        return $this->belongsToMany(Poem::class, 'poem_tag');
    }
}
