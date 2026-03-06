<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $primaryKey = 'key';
    protected $fillable = ['key', 'value'];

    private const ENCRYPTED_KEYS = ['deepseek_api_key'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = self::find($key);
        if (!$row || $row->value === null) {
            return $default;
        }
        if (in_array($key, self::ENCRYPTED_KEYS, true)) {
            try {
                return Crypt::decryptString($row->value);
            } catch (\Throwable) {
                return $default;
            }
        }
        return $row->value;
    }

    public static function set(string $key, ?string $value): void
    {
        if (in_array($key, self::ENCRYPTED_KEYS, true) && $value !== null) {
            $value = Crypt::encryptString($value);
        }
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
