<?php

return [
    'optimizers' => [
        Spatie\ImageOptimizer\Optimizers\Jpegoptim::class => [
            '-m85',
            '--strip-all',
            '--all-progressive',
        ],
        Spatie\ImageOptimizer\Optimizers\Pngquant::class => [
            '--force',
            '--quality=70-85',
        ],
        Spatie\ImageOptimizer\Optimizers\Cwebp::class => [
            '-m',
            '6',
            '-pass',
            '10',
            '-mt',
            '-q',
            '80',
        ],
        Spatie\ImageOptimizer\Optimizers\Svgo::class => [
            '--disable=cleanupIDs',
        ],
        Spatie\ImageOptimizer\Optimizers\Gifsicle::class => [
            '-b',
            '-O3',
        ],
    ],
    'binary_path' => env('IMAGE_OPTIMIZER_BINARY_PATH', null),
    'timeout' => 60,
];