<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Widget extends Model
{
    protected $fillable = [
        'widget_area_id',
        'type',
        'title',
        'settings',
        'visibility',
        'status',
        'sort_order'
    ];

    protected $casts = ['settings' => 'array', 'visibility' => 'array'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(WidgetArea::class, 'widget_area_id');
    }

    public function duplicate(): self
    {
        return self::create([
            'widget_area_id' => $this->widget_area_id,
            'type' => $this->type,
            'title' => $this->title,
            'settings' => $this->settings,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'sort_order' => (int) (self::where('widget_area_id', $this->widget_area_id)->max('sort_order') ?? 0) + 10,
        ]);
    }
}