<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditorUploadController extends Controller
{
    /**
     * POST /admin/ckeditor/upload
     * Accepts a file field named "upload" and returns { url: "..." }.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'upload' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/svg+xml', 'max:5120'],
        ]);

        $file = $request->file('upload');

        // Put wherever you want; this will land in storage/app/public/editor
        $path = $file->storeAs(
            'editor/' . now()->format('Y/m'),
            Str::uuid()->toString() . '.' . $file->getClientOriginalExtension(),
            ['disk' => 'public']
        );

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}