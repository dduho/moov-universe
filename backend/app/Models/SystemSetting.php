<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /** Mémo par requête : un même réglage est souvent lu plusieurs fois par appel API. */
    private static array $memo = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::$memo = []);
        static::deleted(fn () => static::$memo = []);
    }

    public static function getValue($key, $default = null)
    {
        if (!array_key_exists($key, static::$memo)) {
            static::$memo[$key] = self::where('key', $key)->first();
        }

        $setting = static::$memo[$key];

        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function setValue($key, $value, $type = 'string', $description = null)
    {
        $valueToStore = is_array($value) ? json_encode($value) : $value;

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $valueToStore,
                'type' => $type,
                'description' => $description,
            ]
        );
    }
}
