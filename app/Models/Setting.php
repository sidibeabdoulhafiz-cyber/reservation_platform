<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    private static function all_values(): array
    {
        return Cache::rememberForever('settings.all', fn () => static::pluck('value', 'key')->all());
    }

    public static function get($key, $default = null)
    {
        $values = self::all_values();

        return $values[$key] ?? $default;
    }

    public static function set($key, $value)
    {
        $setting = static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');

        return $setting;
    }
}
