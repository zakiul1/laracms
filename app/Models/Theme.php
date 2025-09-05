<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = ['name', 'slug', 'status', 'metadata'];
    protected $casts = ['metadata' => 'array'];

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }
}