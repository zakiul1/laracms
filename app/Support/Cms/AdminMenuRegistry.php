<?php

namespace App\Support\Cms;

class AdminMenuRegistry
{
    /** @var array<int,array{key:string,label:string,url:string,icon?:string,order?:int}> */
    protected array $items = [];

    public function seedBaseline(): void
    {
        // dashboard already hard-coded in view, CPT menus will be added in Phase 1.
        $this->add(['key' => 'modules', 'label' => 'Modules', 'url' => route('admin.dashboard') . '#modules', 'icon' => '🧩', 'order' => 90]);
        $this->add(['key' => 'settings', 'label' => 'Settings', 'url' => route('admin.dashboard') . '#settings', 'icon' => '⚙️', 'order' => 100]);
    }

    public function add(array $item): void
    {
        $item['order'] = $item['order'] ?? 50;
        $this->items[] = $item;
    }

    /** @return array<int,array> sorted */
    public function list(): array
    {
        usort($this->items, fn($a, $b) => ($a['order'] ?? 50) <=> ($b['order'] ?? 50));
        return $this->items;
    }
}