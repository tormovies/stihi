<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    protected $fillable = ['path', 'meta_title', 'meta_description'];

    public static function findByPath(string $path): ?self
    {
        $path = trim($path, '/');
        return $path !== '' ? self::where('path', $path)->first() : null;
    }
}
