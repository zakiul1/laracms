<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Models\AppearanceSetting;
use App\Support\Theme;
use Illuminate\Http\Request;

class CustomizerController extends Controller
{
    public function index()
    {
        $payload = AppearanceSetting::get('customizer', []);
        return view('admin.appearance.customize.index', [
            'data' => $payload,
            'activeTheme' => Theme::activeSlug(),
        ]);
    }

    public function save(Request $r)
    {
        // Store everything as JSON – (logo, colors, typography, layout, etc.)
        $payload = $r->validate(['data' => 'array']);
        AppearanceSetting::put('customizer', $payload['data'] ?? []);
        return back()->with('success', 'Customization saved.');
    }
}