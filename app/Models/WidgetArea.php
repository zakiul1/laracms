<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WidgetArea extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'theme'];

    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class)->orderBy('position');
    }
}