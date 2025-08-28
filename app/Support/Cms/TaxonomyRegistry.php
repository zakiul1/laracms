<?php
namespace App\Support\Cms;

class TaxonomyRegistry
{
    /** @var array<string,array> */
    protected array $taxonomies = [];

    /** @param string|array $objectType CPT slug or array of slugs */
    public function register(string $taxonomy, $objectType, array $args = []): void
    {
        $taxonomy = strtolower($taxonomy);
        $objectType = is_array($objectType) ? array_map('strtolower', $objectType) : [strtolower($objectType)];

        $this->taxonomies[$taxonomy] = array_merge([
            'label' => ucfirst($taxonomy),
            'object_type' => $objectType,
            'hierarchical' => true,
            'menu_position' => 30,
            'menu_icon' => 'tags',     // lucide slug
            'public' => true,
            'rewrite' => ['slug' => $taxonomy],
        ], $args);
    }

    /** @return array<string,array> */
    public function all(): array
    {
        return $this->taxonomies;
    }
}