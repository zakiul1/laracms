<?php

namespace App\Support\Appearance;

use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class AssetManager
{
    protected static ?self $instance = null;
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /** @var array<string,array> */
    protected array $styles = [];
    /** @var array<string,array> */
    protected array $scripts = [];
    protected array $enqStyles = [];
    protected array $enqScriptsHead = [];
    protected array $enqScriptsFooter = [];

    /* ---------------- Register ---------------- */

    public function registerStyle(string $handle, string $src, array $deps = [], ?string $ver = null, string $media = 'all'): void
    {
        $this->styles[$handle] = compact('src', 'deps', 'ver', 'media');
    }

    public function registerScript(string $handle, string $src, array $deps = [], ?string $ver = null, bool $inFooter = true, array $extra = []): void
    {
        $this->scripts[$handle] = compact('src', 'deps', 'ver', 'inFooter', 'extra');
    }

    /* ---------------- Enqueue ---------------- */

    public function enqueueStyle(string $handle): void
    {
        if (!in_array($handle, $this->enqStyles, true))
            $this->enqStyles[] = $handle;
    }

    public function enqueueScript(string $handle, ?bool $inFooter = null): void
    {
        $def = $this->scripts[$handle] ?? null;
        $footer = $inFooter ?? ($def['inFooter'] ?? true);
        $queue = $footer ? 'enqScriptsFooter' : 'enqScriptsHead';
        if (!in_array($handle, $this->$queue, true))
            $this->$queue[] = $handle;
    }

    /* ---------------- Render ---------------- */

    public function renderHead(): HtmlString
    {
        $out = [];
        foreach ($this->order($this->styles, $this->enqStyles) as $h) {
            $s = $this->styles[$h];
            $src = $this->ver($s['src'], $s['ver']);
            $media = e($s['media'] ?? 'all');
            $out[] = "<link rel=\"stylesheet\" href=\"" . e($src) . "\" media=\"{$media}\">";
        }
        foreach ($this->order($this->scripts, $this->enqScriptsHead) as $h) {
            $s = $this->scripts[$h];
            $src = $this->ver($s['src'], $s['ver']);
            $defer = Arr::get($s, 'extra.defer') ? ' defer' : '';
            $out[] = "<script src=\"" . e($src) . "\"{$defer}></script>";
        }
        return new HtmlString(implode("\n", $out) . "\n");
    }

    public function renderFooter(): HtmlString
    {
        $out = [];
        foreach ($this->order($this->scripts, $this->enqScriptsFooter) as $h) {
            $s = $this->scripts[$h];
            $src = $this->ver($s['src'], $s['ver']);
            $defer = Arr::get($s, 'extra.defer') ? ' defer' : '';
            $out[] = "<script src=\"" . e($src) . "\"{$defer}></script>";
        }
        return new HtmlString(implode("\n", $out) . "\n");
    }

    /* ---------------- Helpers ---------------- */

    protected function order(array $reg, array $enq): array
    {
        // Topo-sort limited to enqueued handles + their deps
        $need = [];
        $stack = $enq;
        while ($stack) {
            $h = array_shift($stack);
            if (!isset($reg[$h]) || isset($need[$h]))
                continue;
            $need[$h] = true;
            foreach ($reg[$h]['deps'] ?? [] as $d)
                $stack[] = $d;
        }

        $seen = [];
        $out = [];
        $visit = function ($h) use (&$visit, &$seen, &$out, $reg) {
            if (isset($seen[$h]))
                return;
            $seen[$h] = true;
            foreach (($reg[$h]['deps'] ?? []) as $d)
                if (isset($reg[$d]))
                    $visit($d);
            $out[] = $h;
        };
        foreach (array_keys($need) as $h)
            $visit($h);
        return $out;
    }

    protected function ver(string $src, ?string $ver): string
    {
        if ($ver === null)
            return $src;
        return $src . (str_contains($src, '?') ? '&' : '?') . 'ver=' . rawurlencode($ver);
    }
}