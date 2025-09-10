<?php

namespace App\Shortcodes;

use App\Support\Shortcode\ShortcodeManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class Defaults
{
    public static function register(ShortcodeManager $sc): void
    {
        // [year]
        $sc->add('year', function ($atts, $content) {
            return date('Y');
        });

        // [site_name]
        $sc->add('site_name', function ($atts, $content) {
            return e(Config::get('app.name', 'Laravel'));
        });

        // [button url="..." target="_blank" class="..."]Label[/button]
        $sc->add('button', function ($atts, $content) use ($sc) {
            $a = $sc->atts([
                'url' => '#',
                'target' => '_self',
                'class' => 'btn btn-primary',
                'rel' => '',
            ], $atts, 'button');

            $label = $content ?: 'Click';
            $rel = $a['rel'] ? ' rel="' . e($a['rel']) . '"' : '';
            return '<a href="' . e($a['url']) . '" target="' . e($a['target']) . '" class="' . e($a['class']) . '"' . $rel . '>'
                . e($label) . '</a>';
        });

        // [post_link id="123"] (text from content or post title)
        $sc->add('post_link', function ($atts, $content) use ($sc) {
            $a = $sc->atts(['id' => 0], $atts, 'post_link');
            $post = \App\Models\Post::query()->find((int) $a['id']);
            if (!$post)
                return '';
            $text = trim($content) !== '' ? $content : $post->title;
            $url = url(($post->type === 'page' ? '/page/' : '/post/') . $post->slug);
            return '<a href="' . e($url) . '">' . e($text) . '</a>';
        });

        // Example enclosing + nesting: [box class="notice"]Content [year][/box]
        $sc->add('box', function ($atts, $content) use ($sc) {
            $a = $sc->atts(['class' => 'box'], $atts, 'box');
            return '<div class="' . e($a['class']) . '">' . $content . '</div>';
        });
    }
}