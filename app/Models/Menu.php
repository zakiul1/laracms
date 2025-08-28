<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['slug', 'name'];

    public function items()
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }
}