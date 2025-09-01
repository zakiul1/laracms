<?php

namespace App\Support\Hooks;

/**
 * WordPress-like hook manager for actions & filters.
 * - Priority-ordered execution
 * - add_action(), do_action(), add_filter(), apply_filters() equivalents
 */
class HookManager
{
    /** @var array<string, array<int, array<int, callable>>> $actions[tag][priority][] = cb */
    protected array $actions = [];

    /** @var array<string, array<int, array<int, callable>>> $filters[tag][priority][] = cb */
    protected array $filters = [];

    /** @var array<int, string> Execution stack of current hook names */
    protected array $stack = [];

    /** @var array<string, int> Count of how many times an action was fired */
    protected array $did = [];

    // -------- Actions --------

    public function addAction(string $tag, callable $cb, int $priority = 10): void
    {
        $this->actions[$tag][$priority][] = $cb;
    }

    public function removeAction(string $tag, ?callable $cb = null, ?int $priority = null): void
    {
        if (!isset($this->actions[$tag]))
            return;
        if ($cb === null && $priority === null) {
            unset($this->actions[$tag]);
            return;
        }
        if ($priority !== null && isset($this->actions[$tag][$priority])) {
            if ($cb === null) {
                unset($this->actions[$tag][$priority]);
                return;
            }
            $this->actions[$tag][$priority] = array_values(array_filter(
                $this->actions[$tag][$priority],
                fn($fn) => $fn !== $cb
            ));
            if (!$this->actions[$tag][$priority])
                unset($this->actions[$tag][$priority]);
        }
    }

    public function hasAction(string $tag): bool
    {
        return !empty($this->actions[$tag]);
    }

    public function currentHook(): ?string
    {
        return $this->stack ? end($this->stack) : null;
    }

    public function didAction(string $tag): int
    {
        return $this->did[$tag] ?? 0;
    }

    public function doAction(string $tag, ...$args): void
    {
        $this->stack[] = $tag;
        $this->did[$tag] = ($this->did[$tag] ?? 0) + 1;

        if (!empty($this->actions[$tag])) {
            $priorities = array_keys($this->actions[$tag]);
            sort($priorities, SORT_NUMERIC);

            foreach ($priorities as $p) {
                foreach ($this->actions[$tag][$p] as $cb) {
                    $cb(...$args);
                }
            }
        }

        array_pop($this->stack);
    }

    // -------- Filters --------

    public function addFilter(string $tag, callable $cb, int $priority = 10): void
    {
        $this->filters[$tag][$priority][] = $cb;
    }

    public function removeFilter(string $tag, ?callable $cb = null, ?int $priority = null): void
    {
        if (!isset($this->filters[$tag]))
            return;
        if ($cb === null && $priority === null) {
            unset($this->filters[$tag]);
            return;
        }
        if ($priority !== null && isset($this->filters[$tag][$priority])) {
            if ($cb === null) {
                unset($this->filters[$tag][$priority]);
                return;
            }
            $this->filters[$tag][$priority] = array_values(array_filter(
                $this->filters[$tag][$priority],
                fn($fn) => $fn !== $cb
            ));
            if (!$this->filters[$tag][$priority])
                unset($this->filters[$tag][$priority]);
        }
    }

    public function hasFilter(string $tag): bool
    {
        return !empty($this->filters[$tag]);
    }

    public function applyFilters(string $tag, $value, ...$args)
    {
        $this->stack[] = $tag;

        if (!empty($this->filters[$tag])) {
            $priorities = array_keys($this->filters[$tag]);
            sort($priorities, SORT_NUMERIC);

            foreach ($priorities as $p) {
                foreach ($this->filters[$tag][$p] as $cb) {
                    $value = $cb($value, ...$args);
                }
            }
        }

        array_pop($this->stack);
        return $value;
    }
}