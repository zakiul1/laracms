<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermTaxonomy extends Model
{
    protected $fillable = ['term_id', 'taxonomy', 'description'];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}