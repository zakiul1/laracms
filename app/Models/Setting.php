<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];
    protected $casts = ['value' => 'array'];

    public static function getValue(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        if (!$row)
            return $default;
        $val = $row->value;
        // allow scalar storage too
        if (is_array($val) && array_key_exists('__scalar', $val))
            return $val['__scalar'];
        return $val;
    }

    public static function setValue(string $key, $value, ?string $group = null): void
    {
        $payload = is_array($value) ? $value : ['__scalar' => $value];
        static::updateOrCreate(['key' => $key], ['value' => $payload, 'group' => $group]);
    }
}