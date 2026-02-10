<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_PREFIX = 'site_setting:';

    /**
     * Получить значение по ключу.
     */
    public static function get(string $key, string $default = ''): string
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $value = Cache::get($cacheKey);
        if ($value !== null) {
            return (string) $value;
        }
        $row = self::where('key', $key)->first();
        $value = $row ? (string) $row->value : $default;
        Cache::put($cacheKey, $value, now()->addDay());
        return $value;
    }

    /**
     * Установить значение по ключу.
     */
    public static function set(string $key, ?string $value): void
    {
        $value = $value === null ? '' : $value;
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget(self::CACHE_PREFIX . $key);
    }
}
