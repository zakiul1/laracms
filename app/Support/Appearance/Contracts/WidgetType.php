<?php
namespace App\Support\Appearance\Contracts;

use App\Models\Widget;

interface WidgetType
{
    public static function key(): string;            // e.g. 'text'
    public static function label(): string;          // e.g. 'Text/HTML'
    public static function defaults(): array;        // default settings
    public static function render(array $settings, ?string $title = null): string;
    public static function form(Widget $widget): string; // admin settings html
}