<?php
namespace App\Support\Appearance;

use App\Support\Appearance\Contracts\WidgetType;
use InvalidArgumentException;

class WidgetRegistry
{
    /** @var array<string,class-string<WidgetType>> */
    protected array $types = [];

    public function register(string $key, string $class): void
    {
        $this->types[$key] = $class;
    }

    /** @return array<string,class-string<WidgetType>> */
    public function all(): array
    {
        return $this->types;
    }

    /** @return class-string<WidgetType>|null */
    public function get(string $key): ?string
    {
        return $this->types[$key] ?? null;
    }

    public function render(string $key, array $settings = [], ?string $title = null): string
    {
        $class = $this->get($key);
        if (!$class)
            throw new InvalidArgumentException("Unknown widget: {$key}");
        $settings = array_replace(($class)::defaults(), $settings);
        return ($class)::render($settings, $title);
    }
}