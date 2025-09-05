<?php

namespace App\Http\Controllers\Admin\Appearance;

use App\Http\Controllers\Controller;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class EditorController extends Controller
{
    public function index()
    {
        $root = Theme::dir();
        return view('admin.appearance.editor.index', ['root' => $root]);
    }

    public function read(Request $r)
    {
        $path = $this->resolvePath($r->query('path', ''));
        if (!File::isFile($path))
            abort(404);
        return response()->json([
            'path' => $path,
            'content' => File::get($path),
        ]);
    }

    public function save(Request $r)
    {
        $data = $r->validate(['path' => 'required', 'content' => 'required']);
        $path = $this->resolvePath($data['path']);
        // naive “safe mode”: only allow files under the active theme dir
        if (!str_starts_with($path, Theme::dir() . DIRECTORY_SEPARATOR))
            abort(403);
        File::put($path, $data['content']);
        return response()->json(['ok' => true]);
    }

    protected function resolvePath(string $path): string
    {
        $root = Theme::dir();
        $clean = str_replace(['..', "\0"], '', $path);
        return realpath($root . DIRECTORY_SEPARATOR . $clean) ?: ($root . DIRECTORY_SEPARATOR . $clean);
    }
}