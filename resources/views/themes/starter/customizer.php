<?php

return [
    'title' => 'My Fancy Theme',
    'sections' => [
        'branding' => [
            'label' => 'Branding',
            'fields' => [
                'site_title' => [
                    'type' => 'text',
                    'label' => 'Site Title',
                    'default' => 'LaraCMS',
                    'rules' => 'nullable|string|max:140',
                ],
                'logo' => [
                    'type' => 'image', // render as URL text input (or wire your media picker)
                    'label' => 'Logo URL',
                    'default' => '',
                    'rules' => 'nullable|url|max:500',
                ],
                'primary' => [
                    'type' => 'color',
                    'label' => 'Primary Color',
                    'default' => '#0ea5e9',
                    'rules' => 'nullable|string|max:20',
                ],
                'accent' => [
                    'type' => 'color',
                    'label' => 'Accent Color',
                    'default' => '#f97316',
                    'rules' => 'nullable|string|max:20',
                ],
            ],
        ],
        'layout' => [
            'label' => 'Layout',
            'fields' => [
                'container_width' => [
                    'type' => 'select',
                    'label' => 'Container Width',
                    'choices' => [
                        'boxed' => 'Boxed',
                        'full' => 'Full width',
                    ],
                    'default' => 'full',
                    'rules' => 'required|in:boxed,full',
                ],
                'custom_css' => [
                    'type' => 'textarea',
                    'label' => 'Custom CSS',
                    'default' => '',
                    'rules' => 'nullable|string',
                ],
            ],
        ],
    ],
];