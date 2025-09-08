<?php
namespace App\Support\Appearance;

use App\Models\WidgetArea;
use Illuminate\Support\Facades\Auth;

class WidgetRenderer
{
    public function renderArea(string $slug): string
    {
        $area = WidgetArea::where('slug', $slug)->with('widgets')->first();
        if (!$area)
            return '';

        $html = '';
        foreach ($area->widgets as $w) {
            if ($w->status !== 'active')
                continue;
            if (!$this->passesVisibility($w->visibility ?? []))
                continue;

            $html .= app(WidgetRegistry::class)->render($w->type, $w->settings ?? [], $w->title ?? null);
        }
        return $html;
    }

    public function renderWidgetByKey(string $key, array $settings = [], ?string $title = null): string
    {
        return app(WidgetRegistry::class)->render($key, $settings, $title);
    }

    /** Very small rule engine */
    protected function passesVisibility(array $vis): bool
    {
        if (!$vis)
            return true;
        $mode = ($vis['mode'] ?? 'show') === 'show';
        $rules = $vis['rules'] ?? [];
        $ok = true;

        foreach ($rules as $r) {
            if ($r === 'home')
                $ok = $ok && request()->is('/');
            if ($r === 'logged_in')
                $ok = $ok && Auth::check();
            if ($r === 'guest')
                $ok = $ok && !Auth::check();
            if (str_starts_with($r, 'path_is:'))
                $ok = $ok && request()->is(substr($r, 8));
            if (str_starts_with($r, 'path_starts:'))
                $ok = $ok && str_starts_with(request()->path(), substr($r, 12));
        }
        return $mode ? $ok : !$ok;
    }
}