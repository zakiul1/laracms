<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'version',
        'is_active',
        'meta',
        'sort_order',
        'autoload', // ← add these
    ];

    protected $casts = [
        'is_active' => 'bool',
        'meta' => 'array',
        'autoload' => 'bool',
        'sort_order' => 'int',
    ];
}