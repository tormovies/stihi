<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GonePath extends Model
{
    protected $fillable = ['path'];

    /**
     * Проверка: отдавать ли по этому пути 410 (с учётом варианта с/без завершающего слэша).
     */
    public static function isGone(string $requestPath): bool
    {
        $norm = rtrim($requestPath, '/');
        if ($norm === '') {
            return false;
        }
        return static::query()
            ->where(function ($q) use ($requestPath, $norm) {
                $q->where('path', $requestPath)
                    ->orWhere('path', $requestPath . '/')
                    ->orWhere('path', $norm)
                    ->orWhere('path', $norm . '/');
            })
            ->exists();
    }
}
