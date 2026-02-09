<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug', 'title', 'body',
        'meta_title', 'meta_description', 'h1', 'h1_description',
        'is_published', 'is_home',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_home' => 'boolean',
        ];
    }
}
