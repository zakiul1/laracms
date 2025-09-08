<?php
namespace App\Widgets;

use App\Models\Widget;
use App\Models\Post;
use App\Support\Appearance\Contracts\WidgetType;

class RecentPostsWidget implements WidgetType
{
    public static function key(): string
    {
        return 'recent_posts';
    }
    public static function label(): string
    {
        return 'Recent Posts';
    }
    public static function defaults(): array
    {
        return ['limit' => 5];
    }

    public static function render(array $s, ?string $title = null): string
    {
        $posts = Post::published()->latest()->limit((int) ($s['limit'] ?? 5))->get(['id', 'title', 'slug', 'published_at']);
        if ($posts->isEmpty())
            return '';
        $out = $title ? "<h3 class=\"widget-title\">" . e($title) . "</h3>" : '';
        $out .= "<ul class=\"widget-list\">";
        foreach ($posts as $p) {
            $out .= "<li><a href=\"" . route('post.show', $p->slug) . "\">" . e($p->title) . "</a></li>";
        }
        return "<div class=\"widget widget-recent-posts\">{$out}</ul></div>";
    }

    public static function form(Widget $w): string
    {
        $limit = (int) ($w->settings['limit'] ?? 5);
        return view('admin.appearance.widgets.types.recent_posts', compact('w', 'limit'))->render();
    }
}