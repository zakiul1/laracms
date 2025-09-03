<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plugin extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'version',
        'author',
        'homepage',
        'path',
        'description',
        'enabled',
        'update_url',
        'checksum',
        'requires',
        'extra'
    ];

    protected $casts = [
        'enabled' => 'bool',
        'requires' => 'array',
        'extra' => 'array',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(PluginSetting::class);
    }

    public function getBasePath(): string
    {
        return $this->path ?: base_path('plugins/' . $this->slug);
    }

    public function isActive(): bool
    {
        return $this->enabled === true;
    }
}