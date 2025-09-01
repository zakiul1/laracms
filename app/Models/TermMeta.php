<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermMeta extends Model
{
    protected $table = 'term_meta';

    protected $fillable = [
        'term_id',
        'key',
        'value',
    ];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}