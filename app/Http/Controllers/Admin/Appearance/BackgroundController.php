<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Models\AppearanceSetting;
use Illuminate\Http\Request;

class BackgroundController extends Controller
{
    public function index()
    {
        $val = AppearanceSetting::get('background', []);
        return view('admin.appearance.background.index', ['data' => $val]);
    }
    public function save(Request $r)
    {
        $data = $r->validate([
            'image_id' => ['nullable', 'integer'], // use your media picker
            'color' => ['nullable', 'string'],
            'repeat' => ['nullable', 'in:no-repeat,repeat,repeat-x,repeat-y'],
            'position' => ['nullable', 'string'],
        ]);
        AppearanceSetting::put('background', $data);
        return back()->with('success', 'Background saved.');
    }
}