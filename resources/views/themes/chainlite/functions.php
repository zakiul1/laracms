<?php

use Illuminate\Support\Facades\Event;

$ver = '1.0.0';
$dist = theme_path('assets/dist');

// if you add a manifest later, this supports it
$manifest = @json_decode(@file_get_contents($dist . '/manifest.json'), true) ?: [];
$pick = static fn(string $name) => $manifest[$name] ?? $name;

app()->booted(function () use ($dist, $ver, $pick) {
    Event::listen('enqueue.front', function () use ($dist, $ver, $pick) {
        $css = $pick('theme.css');
        if (is_file($dist . '/' . $css)) {
            register_style('chainlite', theme_asset('assets/dist/' . $css), [], $ver);
            enqueue_style('chainlite');
        }
        // Optional JS if you add it:
        $js = $pick('theme.js');
        if (is_file($dist . '/' . $js)) {
            register_script('chainlite', theme_asset('assets/dist/' . $js), [], $ver, true, ['defer' => true]);
            enqueue_script('chainlite');
        }
    });
});

// legacy include if you still use theme.php
$legacy = theme_path('theme.php');
if (is_file($legacy))
    require_once $legacy;