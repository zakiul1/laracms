<?php
namespace App\Widgets;

use App\Models\Widget;
use App\Support\Appearance\Contracts\WidgetType;

class SearchWidget implements WidgetType
{
    public static function key(): string
    {
        return 'search';
    }
    public static function label(): string
    {
        return 'Search';
    }
    public static function defaults(): array
    {
        return [];
    }

    public static function render(array $s, ?string $title = null): string
    {
        $titleHtml = $title ? "<h3 class=\"widget-title\">" . e($title) . "</h3>" : '';
        $form = '<form method="GET" action="' . route('search') . '"><input name="q" class="w-full border rounded px-2 py-1" placeholder="Search..."></form>';
        return "<div class=\"widget widget-search\">{$titleHtml}{$form}</div>";
    }

    public static function form(Widget $w): string
    {
        return '';
    }
}