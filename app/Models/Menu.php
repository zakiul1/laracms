<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    protected $casts = [
        'id' => 'int',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $menu) {
            if (!filled($menu->slug) && filled($menu->name)) {
                $menu->slug = Str::slug($menu->name);
            }
        });
    }

    public function items(): HasMany
    {
        // All items for this menu, consistently ordered
        return $this->hasMany(MenuItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function roots(): HasMany
    {
        // Only top-level items (used in editor)
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(MenuLocation::class);
    }
}