<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'taxonomy_id',
        'name',
        'slug',
        'parent_id',
        'description',
    ];

    // <-- THIS fixes your error
    public function taxonomy()
    {
        return $this->belongsTo(Taxonomy::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    public function meta()
    {
        return $this->hasMany(TermMeta::class);
    }


    // Posts attached to this term (via pivot post_term)
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_term')
            ->withTimestamps();
    }
    public function media()
    {
        $tr = (new \App\Models\TermRelationship())->getTable();
        $tt = (new \App\Models\TermTaxonomy())->getTable();

        return $this->belongsToMany(
            \App\Models\Media::class,
            $tr,
            'term_taxonomy_id',
            'object_id'
        )->join($tt, "{$tr}.term_taxonomy_id", '=', "{$tt}.id")
            ->where("{$tt}.term_id", $this->id)
            ->where("{$tt}.taxonomy", 'media_category');
    }

}