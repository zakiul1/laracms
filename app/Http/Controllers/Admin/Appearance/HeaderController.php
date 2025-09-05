<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Models\AppearanceSetting;
use Illuminate\Http\Request;

class HeaderController extends Controller
{
    public function index()
    {
        $val = AppearanceSetting::get('header', []);
        return view('admin.appearance.header.index', ['data' => $val]);
    }
    public function save(Request $r)
    {
        $data = $r->validate([
            'image_id' => ['nullable', 'integer'],
            'height' => ['nullable', 'integer'],
            'alignment' => ['nullable', 'in:left,center,right'],
        ]);
        AppearanceSetting::put('header', $data);
        return back()->with('success', 'Header saved.');
    }
}