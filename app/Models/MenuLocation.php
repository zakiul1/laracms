<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuLocation extends Model
{
    protected $fillable = ['name', 'slug', 'menu_id'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}