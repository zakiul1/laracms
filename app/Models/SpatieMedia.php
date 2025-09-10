<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class SpatieMedia extends BaseMedia
{
    // keep Spatie completely separate from your legacy `media` table
    protected $table = 'spatie_media';
}