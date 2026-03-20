<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class UrlRedirect extends Model
{
    public const CACHE_KEY = 'url_redirects.map';

    protected $fillable = ['from_path', 'to_path'];

    protected static function booted(): void
    {
        static::saved(static fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(static fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Нормализация пути как в БД: без слэшей по краям, нижний регистр.
     */
    public static function normalizePath(string $path): string
    {
        $path = trim($path);
        $path = trim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        return mb_strtolower((string) $path);
    }

    /** from_path / to_path для сохранения из формы (ввод со слэшами допустим). */
    public static function normalizeForStorage(string $path): string
    {
        return self::normalizePath($path);
    }

    /**
     * Карта from_path => to_path (кэш).
     *
     * @return array<string, string>
     */
    public static function redirectMap(): array
    {
        return Cache::remember(self::CACHE_KEY, 86400, function () {
            return static::query()->pluck('to_path', 'from_path')->all();
        });
    }

    public static function forgetMapCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Абсолютный URL назначения (на сайте ссылки со слэшем в конце). */
    public static function targetUrl(string $toPath): string
    {
        $toPath = self::normalizePath($toPath);

        return url('/' . $toPath . '/');
    }
}
