<?php

namespace App\Widgets;

use App\Support\Appearance\WidgetType;

class TextHtmlWidget implements WidgetType
{
    public static function key(): string
    {
        return 'text_html';
    }
    public static function label(): string
    {
        return 'Text / HTML';
    }
    public static function defaultSettings(): array
    {
        return ['text' => ''];
    }
    public static function adminFormView(): string
    {
        return 'admin.appearance.widgets.types.text_html';
    }

    public static function render(array $settings, ?string $title = null, array $context = []): string
    {
        $text = (string) ($settings['text'] ?? '');
        if ($text === '' && !$title)
            return '';
        return view('theme::widgets.text', compact('title', 'text'))->render();
    }
}