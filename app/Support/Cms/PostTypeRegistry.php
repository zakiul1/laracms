<?php

namespace App\Support\Cms;

class PostTypeRegistry
{
    /** @var array<string,array> */
    protected array $types = [
        'post' => ['label' => 'Posts', 'menu_icon' => '📝', 'menu_order' => 10],
        'page' => ['label' => 'Pages', 'menu_icon' => '📄', 'menu_order' => 20],
    ];

    public function register(string $slug, array $args = []): void
    {
        $defaults = ['label' => ucfirst($slug), 'menu_icon' => '📦', 'menu_order' => 50];
        $this->types[$slug] = array_merge($defaults, $args);
    }

    /** @return array<string,array> */
    public function all(): array
    {
        return $this->types;
    }
}