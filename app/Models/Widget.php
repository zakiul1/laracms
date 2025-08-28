<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $fillable = ['widget_area', 'type', 'config', 'status', 'order'];
    protected $casts = ['config' => 'array', 'status' => 'boolean'];
}