<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginSetting extends Model
{
    protected $fillable = ['plugin_id', 'key', 'value'];
    protected $casts = ['value' => 'array'];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }
}