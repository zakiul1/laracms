<?php
namespace App\Widgets;

use App\Models\Widget;
use App\Support\Appearance\Contracts\WidgetType;
use App\Models\TaxonomyTerm;

class CategoriesWidget implements WidgetType
{
    public static function key(): string
    {
        return 'categories';
    }
    public static function label(): string
    {
        return 'Categories';
    }
    public static function defaults(): array
    {
        return ['dropdown' => false];
    }

    public static function render(array $s, ?string $title = null): string
    {
        $cats = TaxonomyTerm::where('taxonomy', 'category')->orderBy('name')->get(['slug', 'name']);
        if ($cats->isEmpty())
            return '';
        $titleHtml = $title ? "<h3 class=\"widget-title\">" . e($title) . "</h3>" : '';
        $html = "<div class=\"widget widget-categories\">{$titleHtml}";
        if (!empty($s['dropdown'])) {
            $html .= "<select onchange=\"location=this.value\">";
            foreach ($cats as $c)
                $html .= "<option value=\"" . route('category.show', $c->slug) . "\">" . e($c->name) . "</option>";
            $html .= "</select>";
        } else {
            $html .= "<ul class=\"widget-list\">";
            foreach ($cats as $c)
                $html .= "<li><a href=\"" . route('category.show', $c->slug) . "\">" . e($c->name) . "</a></li>";
            $html .= "</ul>";
        }
        return $html . "</div>";
    }

    public static function form(Widget $w): string
    {
        $dropdown = !empty($w->settings['dropdown']);
        return view('admin.appearance.widgets.types.categories', compact('w', 'dropdown'))->render();
    }
}