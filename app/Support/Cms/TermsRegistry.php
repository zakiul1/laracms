<?php
namespace App\Support\Cms;

class TermsRegistry
{
    /** @var array<string,array<int,array{slug:string,name:string,parent?:string}>> */
    protected array $initial = [];

    /** @param array<int,array{slug?:string,name:string,parent?:string}> $terms */
    public function add(string $taxonomy, array $terms): void
    {
        $taxonomy = strtolower($taxonomy);
        $this->initial[$taxonomy] = array_values(array_map(function ($t) {
            return [
                'slug' => strtolower($t['slug'] ?? $t['name']),
                'name' => $t['name'] ?? ucfirst($t['slug']),
                'parent' => $t['parent'] ?? null,
            ];
        }, $terms));
    }

    /** @return array<string,array<int,array>> */
    public function all(): array
    {
        return $this->initial;
    }
}