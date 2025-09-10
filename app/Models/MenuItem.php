<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'type',
        'type_id',
        'target',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'menu_id' => 'int',
        'parent_id' => 'int',
        'type_id' => 'int',
        'sort_order' => 'int',
    ];

    /* ----------------------------------------
     | Relationships
     |-----------------------------------------*/

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        // Keep children always ordered by sort_order
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    /* ----------------------------------------
     | Scopes / helpers
     |-----------------------------------------*/

    /**
     * Items for a given menu ordered by sort_order (top level & nested usage).
     */
    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order');
    }

    /**
     * Only root items (parent_id is null) for a menu.
     */
    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id')->ordered();
    }
}