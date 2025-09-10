<?php

namespace App\Support\Shortcode;

use Closure;

class ShortcodeManager
{
    /** @var array<string, callable> */
    protected array $shortcodes = [];

    /** Register a shortcode handler */
    public function add(string $tag, callable $handler): void
    {
        $this->shortcodes[$tag] = $handler;
    }

    /** Remove a shortcode */
    public function remove(string $tag): void
    {
        unset($this->shortcodes[$tag]);
    }

    /** Remove all */
    public function removeAll(): void
    {
        $this->shortcodes = [];
    }

    /** Is a shortcode registered? */
    public function has(string $tag): bool
    {
        return isset($this->shortcodes[$tag]);
    }

    /** Return all registered tags */
    public function tags(): array
    {
        return array_keys($this->shortcodes);
    }

    /** Compile content by processing shortcodes (recursively) */
    public function compile(?string $content, array $context = []): string
    {
        if (!is_string($content) || $content === '') {
            return (string) $content;
        }

        $tags = $this->tags();
        if (empty($tags)) {
            return $content;
        }

        // Handle [[escaped]] → [unescaped]
        $content = str_replace(['[[', ']]'], ['__SC_ESC_L__', '__SC_ESC_R__'], $content);

        $pattern = $this->regex($tags);

        $that = $this;
        $replacer = function ($m) use ($that, $context) {
            $tag = $m['tag'];
            $attrs = isset($m['attrs']) ? trim($m['attrs']) : '';
            $isSelf = !empty($m['self']);
            $inner = $m['content'] ?? '';

            // Not registered? Return original
            if (!isset($that->shortcodes[$tag])) {
                return $m[0];
            }

            $atts = $that->parseAttributes($attrs);

            // WP parity: allow filters on atts per tag if apply_filters exists
            if (function_exists('apply_filters')) {
                $atts = apply_filters("shortcode_atts_{$tag}", $atts, $context);
            }

            $callback = $that->shortcodes[$tag];

            $compiledInner = $isSelf ? '' : $that->compile($inner, $context);

            try {
                return (string) call_user_func($callback, $atts, $compiledInner, $tag, $context);
            } catch (\Throwable $e) {
                // Fail-safe: return original text if handler crashes
                return $m[0];
            }
        };

        // Recursive replace (PCRE recursion used in pattern)
        $out = preg_replace_callback($pattern, $replacer, $content);

        // Restore escaped brackets
        $out = str_replace(['__SC_ESC_L__', '__SC_ESC_R__'], ['[', ']'], (string) $out);

        return $out;
    }

    /** Strip shortcodes (leave inner text) */
    public function strip(?string $content): string
    {
        if (!is_string($content) || $content === '') {
            return (string) $content;
        }
        $tags = $this->tags();
        if (empty($tags)) {
            return $content;
        }
        $pattern = $this->regex($tags);
        return (string) preg_replace($pattern, '$4', $content); // capture 'content' group index-safe fallback
    }

    /** Does content contain a (specific) shortcode? */
    public function contains(?string $content, ?string $tag = null): bool
    {
        if (!is_string($content) || $content === '')
            return false;

        $tags = $tag ? [$tag] : $this->tags();
        if (empty($tags))
            return false;

        $pattern = $this->regex($tags);
        return (bool) preg_match($pattern, $content);
    }

    /** Merge attributes like WordPress `shortcode_atts` */
    public function atts(array $pairs, array $atts, string $shortcode = ''): array
    {
        $out = [];
        foreach ($pairs as $name => $default) {
            $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
        }
        // include any extra unrecognized attributes
        foreach ($atts as $name => $value) {
            if (!array_key_exists($name, $out))
                $out[$name] = $value;
        }
        if (function_exists('apply_filters') && $shortcode) {
            $out = apply_filters("shortcode_atts_{$shortcode}", $out, $atts);
        }
        return $out;
    }

    /** Build recursive regex similar to WP get_shortcode_regex() */
    protected function regex(array $tags): string
    {
        $t = array_map(static fn($x) => preg_quote($x, '/'), $tags);
        $tag = implode('|', $t);

        // Named groups: tag, attrs, self, content
        // Supports: [tag], [tag /], [tag attr="x"]content[/tag], nesting via (?R).
        return '/\[(?P<tag>' . $tag . ')\b(?P<attrs>[^\]\/]*(?:\/(?!\])[^\]\/]*)*?)' .
            '(?:(?P<self>\/)\]|\]' .
            '(?P<content>(?>[^\[]+|(?R))*?)' .
            '\[\/\1\])' .
            '/s';
    }

    /** Attribute parser (key="v", key='v', key=v, "bare", 'bare', bare) */
    protected function parseAttributes(string $text): array
    {
        $atts = [];
        $pattern = '/
(\w+)\s*=\s*"([^"]*)" # key="value"
|(\w+)\s*=\s*\'([^\']*)\' # key=\'value\'
|(\w+)\s*=\s*([^\s\'"]+) # key=value
|"([^"]+)" # "bare"
|\'([^\']+)\' # \'bare\'
|(\S+) # bare
/x';

        if (preg_match_all($pattern, $text, $m, PREG_SET_ORDER)) {
            foreach ($m as $p) {
                if (!empty($p[1]))
                    $atts[strtolower($p[1])] = $p[2];
                elseif (!empty($p[3]))
                    $atts[strtolower($p[3])] = $p[4];
                elseif (!empty($p[5]))
                    $atts[strtolower($p[5])] = $p[6];
                elseif (!empty($p[7]))
                    $atts[] = $p[7];
                elseif (!empty($p[8]))
                    $atts[] = $p[8];
                elseif (!empty($p[9]))
                    $atts[] = $p[9];
            }
        }

        return $atts;
    }
}