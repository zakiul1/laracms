<?php
namespace App\Widgets;

use App\Models\Widget;
use App\Models\Menu;
use App\Support\Appearance\Contracts\WidgetType;

class MenuWidget implements WidgetType
{
    public static function key(): string
    {
        return 'menu';
    }
    public static function label(): string
    {
        return 'Navigation Menu';
    }
    public static function defaults(): array
    {
        return ['menu_id' => null];
    }

    public static function render(array $s, ?string $title = null): string
    {
        $menu = Menu::with('items.children')->find($s['menu_id'] ?? 0);
        if (!$menu)
            return '';
        $titleHtml = $title ? "<h3 class=\"widget-title\">" . e($title) . "</h3>" : '';
        return "<div class=\"widget widget-menu\">{$titleHtml}" . app(\App\Services\MenuService::class)->renderById($menu->id) . "</div>";
    }

    public static function form(Widget $w): string
    {
        $menus = Menu::orderBy('name')->get(['id', 'name']);
        $current = $w->settings['menu_id'] ?? null;
        return view('admin.appearance.widgets.types.menu', compact('w', 'menus', 'current'))->render();
    }
}