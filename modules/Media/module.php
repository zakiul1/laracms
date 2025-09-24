<?php

use Modules\Media\MediaServiceProvider;

return [
    'name' => 'Media',
    'enabled' => true,
    'providers' => [
        MediaServiceProvider::class,
    ],
];