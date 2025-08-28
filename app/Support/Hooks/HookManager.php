<?php

namespace App\Support\Hooks;

/**
 * WordPress-like hook manager for actions & filters.
 * - Priority-ordered execution
 * - O(1) add, O(P) iterate (P = # of distinct priorities for that hook)
 * - Helpers: ensure(), has*, remove*, didAction(), currentHook()
 */
class HookManager
{
    /**
     * @var array<string, array<int, array<int, callable>>> $actions[tag][priority][] = cb
     */
    protected array $actions = [];

    /**
     * @var array<string, array<int, array<int, callable>>> $filters[tag][priority][] = cb
     */
    protected array $filters = [];

    /** @var array<int, string> Execution stack of hook names */
    protected array $stack = [];

    /** @var array<string, int> Count of times an action has fired */
    protected array $did = [];

    /* -----------------------------
     * Lifecycle / utilities
     * ----------------------------- */

    /** Ensure an action/filter bucket exists (harmless no-op if present). */
    public function ensure(string $tag): void
    {
        $this->actions[$tag] ??= [];
        $this->filters[$tag] ??= [];
    }

    /** Is any callback registered for action $tag ? */
    public function hasAction(string $tag): bool
    {
        return !empty($this->actions[$tag]);
    }

    /** Is any callback registered for filter $tag ? */
    public function hasFilter(string $tag): bool
    {
        return !empty($this->filters[$tag]);
    }

    /** How many times has an action fired? */
    public function didAction(string $tag): int
    {
        return $this->did[$tag] ?? 0;
    }

    /** Name of the currently executing hook (or null). */
    public function currentHook(): ?string
    {
        return $this->stack ? $this->stack[array_key_last($this->stack)] : null;
    }

    /* -----------------------------
     * Actions
     * ----------------------------- */

    public function addAction(string $tag, callable $cb, int $priority = 10): void
    {
        $this->ensure($tag);
        $this->actions[$tag][$priority][] = $cb;
    }

    /** Add an action that runs once, then removes itself. */
    public function addActionOnce(string $tag, callable $cb, int $priority = 10): void
    {
        $wrapper = function (...$args) use ($tag, $cb, $priority, &$wrapper) {
            // Remove the wrapper before invoking to avoid re-entry issues.
            $this->removeAction($tag, $wrapper, $priority);
            $cb(...$args);
        };
        $this->addAction($tag, $wrapper, $priority);
    }

    public function removeAction(string $tag, ?callable $cb = null, ?int $priority = null): void
    {
        if (!isset($this->actions[$tag]))
            return;

        // Remove an entire tag
        if ($cb === null && $priority === null) {
            unset($this->actions[$tag]);
            return;
        }

        $priorities = $priority !== null ? [$priority] : array_keys($this->actions[$tag]);
        foreach ($priorities as $p) {
            if (!isset($this->actions[$tag][$p]))
                continue;

            if ($cb === null) {
                // Remove all callbacks at this priority
                unset($this->actions[$tag][$p]);
                continue;
            }

            // Remove specific callable
            $this->actions[$tag][$p] = array_values(array_filter(
                $this->actions[$tag][$p],
                fn($existing) => $existing !== $cb
            ));
            if (!$this->actions[$tag][$p])
                unset($this->actions[$tag][$p]);
        }

        if (!$this->actions[$tag])
            unset($this->actions[$tag]);
    }

    public function doAction(string $tag, ...$args): void
    {
        $this->stack[] = $tag;
        $this->did[$tag] = ($this->did[$tag] ?? 0) + 1;

        // Iterate priorities in ascending order
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

    /* -----------------------------
     * Filters
     * ----------------------------- */

    public function addFilter(string $tag, callable $cb, int $priority = 10): void
    {
        $this->ensure($tag);
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

        $priorities = $priority !== null ? [$priority] : array_keys($this->filters[$tag]);
        foreach ($priorities as $p) {
            if (!isset($this->filters[$tag][$p]))
                continue;

            if ($cb === null) {
                unset($this->filters[$tag][$p]);
                continue;
            }

            $this->filters[$tag][$p] = array_values(array_filter(
                $this->filters[$tag][$p],
                fn($existing) => $existing !== $cb
            ));
            if (!$this->filters[$tag][$p])
                unset($this->filters[$tag][$p]);
        }

        if (!$this->filters[$tag])
            unset($this->filters[$tag]);
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