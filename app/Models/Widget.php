<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Widget extends Model
{
    protected $fillable = ['widget_area_id', 'type', 'title', 'settings', 'position'];
    protected $casts = ['settings' => 'array'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(WidgetArea::class, 'widget_area_id');
    }
}