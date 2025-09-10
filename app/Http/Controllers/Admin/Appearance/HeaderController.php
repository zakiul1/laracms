<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Models\AppearanceSetting;
use Illuminate\Http\Request;

class HeaderController extends Controller
{
    public function index()
    {
        $activeTheme = config('laracms.active_theme', 'laracms');

        // Load saved settings for this theme, e.g. from appearance_settings table
        $values = optional(
            \DB::table('appearance_settings')->where('key', 'header:' . $activeTheme)->first()
        )->value;

        $values = is_string($values) ? json_decode($values, true) : (is_array($values) ? $values : []);

        return view('admin.appearance.header.index', compact('values', 'activeTheme'));
    }

    public function save(Request $request)
    {
        $activeTheme = config('laracms.active_theme', 'laracms');
        $data = $request->validate([
            'header.image_id' => ['nullable', 'integer'],
            'header.height' => ['nullable', 'integer', 'min:120', 'max:1200'],
            'header.position' => ['nullable', 'string'],
            'header.size' => ['nullable', 'string'],
            'header.repeat' => ['nullable', 'string'],
            'header.overlay_color' => ['nullable', 'string'],
            'header.overlay_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'header.show_title' => ['nullable'],
        ]);

        $payload = $data['header'] ?? [];
        $payload['show_title'] = isset($payload['show_title']) ? (bool) $payload['show_title'] : false;

        \DB::table('appearance_settings')->updateOrInsert(
            ['key' => 'header:' . $activeTheme],
            ['value' => json_encode($payload)]
        );

        return back()->with('success', 'Header settings saved.');
    }

}