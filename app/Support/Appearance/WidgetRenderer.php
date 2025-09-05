<?php

namespace App\Support\Appearance;

use App\Models\WidgetArea;

class WidgetRenderer
{
    public function renderArea(string $slug): string
    {
        $theme = app('appearance.active_theme');
        $area = WidgetArea::where('slug', $slug)
            ->where(function ($q) use ($theme) {
                $q->whereNull('theme_slug')->orWhere('theme_slug', $theme);
            })->first();

        if (!$area)
            return '';
        $out = '';
        foreach ($area->widgets as $w) {
            $out .= $this->renderWidget($w->type, (array) $w->settings);
        }
        return $out;
    }

    protected function renderWidget(string $type, array $settings): string
    {
        return match ($type) {
            'text' => view('theme::widgets.text', ['text' => $settings['text'] ?? ''])->render(),
            'html' => (string) ($settings['html'] ?? ''),
            'recent_posts' => view('theme::widgets.recent-posts', ['limit' => (int) ($settings['limit'] ?? 5)])->render(),
            'categories' => view('theme::widgets.categories')->render(),
            default => '',
        };
    }
}