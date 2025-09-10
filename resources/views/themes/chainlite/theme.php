<?php
// resources/views/themes/chainlite/theme.php

register_sidebar(['slug' => 'footer-1', 'name' => 'Footer 1']);
register_sidebar(['slug' => 'footer-2', 'name' => 'Footer 2']);
register_sidebar(['slug' => 'footer-3', 'name' => 'Footer 3']);

do_action('theme.locations.register', [
    'primary' => 'Primary Navigation',
    'footer' => 'Footer Navigation',
]);

// Example shortcode: [year]
if (!has_shortcode('', 'year')) {
    add_shortcode('year', fn() => date('Y'));
}