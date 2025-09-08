<?php

namespace App\Support\Settings;

class SettingsPageRegistry
{
    /** @var array<string,array> */
    protected array $pages = [];

    public function __construct()
    {
        // --- Core pages ------------------------------------------------------
        $this->register('general', [
            'label' => 'General',
            'icon' => 'lucide-settings',
            'view' => 'admin.settings.tabs.general',
            'order' => 10,
            'defaults' => [
                'site_title' => 'LaraCMS',
                'tagline' => '',
                'site_icon_url' => '',
                'wp_url' => config('app.url'),
                'site_url' => config('app.url'),
                'admin_email' => '',
                'membership_anyone' => false,
                'default_user_role' => 'subscriber',
                'language' => config('app.locale', 'en'),
                'timezone' => config('app.timezone', 'UTC'),
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i',
                'week_starts_on' => 1,
            ],
            'rules' => function () {
                return [
                    'site_title' => ['required', 'string', 'max:140'],
                    'tagline' => ['nullable', 'string', 'max:200'],
                    'site_icon_url' => ['nullable', 'url'],
                    'wp_url' => ['required', 'url'],
                    'site_url' => ['required', 'url'],
                    'admin_email' => ['nullable', 'email'],
                    'membership_anyone' => ['boolean'],
                    'default_user_role' => ['required', 'in:subscriber,author,editor,admin'],
                    'language' => ['required', 'string', 'max:10'],
                    'timezone' => ['required', 'timezone'],
                    'date_format' => ['required', 'string', 'max:32'],
                    'time_format' => ['required', 'string', 'max:32'],
                    'week_starts_on' => ['required', 'integer', 'between:0,6'],
                ];
            },
        ]);

        $this->register('writing', [
            'label' => 'Writing',
            'icon' => 'lucide-pencil',
            'view' => 'admin.settings.tabs.writing',
            'order' => 20,
            'defaults' => [
                'default_post_category' => null,
                'default_post_format' => 'standard',
                'post_via_email' => [
                    'host' => '',
                    'port' => 993,
                    'user' => '',
                    'pass' => '',
                    'ssl' => true,
                    'default_category' => null,
                ],
                'update_services' => '',
            ],
            'rules' => function () {
                return [
                    'default_post_category' => ['nullable', 'integer'],
                    'default_post_format' => ['required', 'string', 'max:20'],
                    'post_via_email.host' => ['nullable', 'string', 'max:120'],
                    'post_via_email.port' => ['nullable', 'integer', 'between:1,65535'],
                    'post_via_email.user' => ['nullable', 'string', 'max:120'],
                    'post_via_email.pass' => ['nullable', 'string', 'max:120'],
                    'post_via_email.ssl' => ['boolean'],
                    'post_via_email.default_category' => ['nullable', 'integer'],
                    'update_services' => ['nullable', 'string'],
                ];
            },
        ]);

        $this->register('reading', [
            'label' => 'Reading',
            'icon' => 'lucide-book-open',
            'view' => 'admin.settings.tabs.reading',
            'order' => 30,
            'defaults' => [
                'home_type' => 'posts', // posts|page
                'home_page_id' => null,
                'blog_page_id' => null,
                'posts_per_page' => 10,
                'feed_items' => 10,
                'feed_show' => 'full', // full|summary
                'discourage_indexing' => false,
            ],
            'rules' => function () {
                return [
                    'home_type' => ['required', 'in:posts,page'],
                    'home_page_id' => ['nullable', 'integer'],
                    'blog_page_id' => ['nullable', 'integer'],
                    'posts_per_page' => ['required', 'integer', 'between:1,100'],
                    'feed_items' => ['required', 'integer', 'between:1,100'],
                    'feed_show' => ['required', 'in:full,summary'],
                    'discourage_indexing' => ['boolean'],
                ];
            },
        ]);

        $this->register('discussion', [
            'label' => 'Discussion',
            'icon' => 'lucide-messages-square',
            'view' => 'admin.settings.tabs.discussion',
            'order' => 40,
            'defaults' => [
                'default_article' => [
                    'notify_blogs' => true,
                    'allow_pingbacks' => true,
                    'allow_comments' => true,
                ],
                'comments' => [
                    'require_name_email' => true,
                    'must_be_registered' => false,
                    'close_after_days' => 0,
                    'threaded_levels' => 3,
                    'paginate' => false,
                    'per_page' => 50,
                    'order' => 'asc',
                    'manual_approve' => true,
                    'auto_approve_prev' => true,
                    'email_notify' => ['new_comment' => true, 'moderation' => true],
                ],
                'avatars' => ['show' => true, 'default' => 'mystery'],
            ],
            'rules' => function () {
                return [
                    'default_article.notify_blogs' => ['boolean'],
                    'default_article.allow_pingbacks' => ['boolean'],
                    'default_article.allow_comments' => ['boolean'],
                    'comments.require_name_email' => ['boolean'],
                    'comments.must_be_registered' => ['boolean'],
                    'comments.close_after_days' => ['integer', 'between:0,3650'],
                    'comments.threaded_levels' => ['integer', 'between:1,10'],
                    'comments.paginate' => ['boolean'],
                    'comments.per_page' => ['integer', 'between:5,200'],
                    'comments.order' => ['in:asc,desc'],
                    'comments.manual_approve' => ['boolean'],
                    'comments.auto_approve_prev' => ['boolean'],
                    'comments.email_notify.new_comment' => ['boolean'],
                    'comments.email_notify.moderation' => ['boolean'],
                    'avatars.show' => ['boolean'],
                    'avatars.default' => ['in:mystery,identicon,monsterid,wavatar,retro,robohash,blank'],
                ];
            },
        ]);

        $this->register('media', [
            'label' => 'Media',
            'icon' => 'lucide-image',
            'view' => 'admin.settings.tabs.media',
            'order' => 50,
            'defaults' => [
                'sizes' => [
                    'thumbnail' => ['w' => 150, 'h' => 150, 'crop' => true],
                    'medium' => ['w' => 300, 'h' => 300, 'crop' => false],
                    'large' => ['w' => 1024, 'h' => 1024, 'crop' => false],
                ],
                'organize_uploads_by_date' => true,
            ],
            'rules' => function () {
                return [
                    'sizes.thumbnail.w' => ['required', 'integer', 'between:1,4000'],
                    'sizes.thumbnail.h' => ['required', 'integer', 'between:1,4000'],
                    'sizes.thumbnail.crop' => ['boolean'],
                    'sizes.medium.w' => ['required', 'integer', 'between:1,8000'],
                    'sizes.medium.h' => ['required', 'integer', 'between:1,8000'],
                    'sizes.medium.crop' => ['boolean'],
                    'sizes.large.w' => ['required', 'integer', 'between:1,12000'],
                    'sizes.large.h' => ['required', 'integer', 'between:1,12000'],
                    'sizes.large.crop' => ['boolean'],
                    'organize_uploads_by_date' => ['boolean'],
                ];
            },
        ]);

        $this->register('permalinks', [
            'label' => 'Permalinks',
            'icon' => 'lucide-link',
            'view' => 'admin.settings.tabs.permalinks',
            'order' => 60,
            'defaults' => [
                'structure' => 'postname', // plain|dayname|monthname|numeric|postname|custom
                'custom' => '/%postname%/',
                'category_base' => '/category',
                'tag_base' => '/tag',
            ],
            'rules' => function () {
                return [
                    'structure' => ['required', 'in:plain,dayname,monthname,numeric,postname,custom'],
                    'custom' => ['nullable', 'string', 'max:120'],
                    'category_base' => ['nullable', 'string', 'max:120'],
                    'tag_base' => ['nullable', 'string', 'max:120'],
                ];
            },
        ]);

        $this->register('privacy', [
            'label' => 'Privacy',
            'icon' => 'lucide-shield',
            'view' => 'admin.settings.tabs.privacy',
            'order' => 70,
            'defaults' => [
                'policy_page_id' => null,
            ],
            'rules' => function () {
                return [
                    'policy_page_id' => ['nullable', 'integer'],
                ];
            },
        ]);
    }

    /**
     * Register/override a settings page.
     *
     * @param string $key
     * @param array  $def ['label','icon','view','order','defaults','rules','capability']
     */
    public function register(string $key, array $def): void
    {
        $this->pages[$key] = array_merge([
            'label' => $key,
            'icon' => null,
            'view' => null,
            'order' => 0,
            'capability' => null,
            'defaults' => [],
            'rules' => fn() => [],
        ], $def);
    }

    /** Whether a page is registered. */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->pages);
    }

    /** @return array<string,array> pages sorted by order then label */
    public function all(): array
    {
        $pages = $this->pages;
        uasort($pages, function ($a, $b) {
            $cmp = ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
            return $cmp !== 0 ? $cmp : strcmp($a['label'] ?? '', $b['label'] ?? '');
        });
        return $pages;
    }

    public function get(string $key): ?array
    {
        return $this->pages[$key] ?? null;
    }

    public function defaults(string $key): array
    {
        return $this->pages[$key]['defaults'] ?? [];
    }

    public function rules(string $key): array
    {
        $rules = $this->pages[$key]['rules'] ?? fn() => [];
        return is_callable($rules) ? $rules() : (array) $rules;
    }

    /** Remove a page. */
    public function forget(string $key): void
    {
        unset($this->pages[$key]);
    }

    /** Convenience: list page keys in sorted order. */
    public function keys(): array
    {
        return array_keys($this->all());
    }
}