<?php
namespace App\Widgets;

use App\Models\Widget;
use App\Support\Appearance\Contracts\WidgetType;

class TextWidget implements WidgetType
{
    public static function key(): string
    {
        return 'text';
    }
    public static function label(): string
    {
        return 'Text / HTML';
    }
    public static function defaults(): array
    {
        return ['content' => ''];
    }

    public static function render(array $s, ?string $title = null): string
    {
        $titleHtml = $title ? "<h3 class=\"widget-title\">" . e($title) . "</h3>" : "";
        return "<div class=\"widget widget-text\">{$titleHtml}" . ($s['content'] ?? '') . "</div>";
    }

    public static function form(Widget $w): string
    {
        $content = $w->settings['content'] ?? '';
        return view('admin.appearance.widgets.types.text', compact('w', 'content'))->render();
    }
}