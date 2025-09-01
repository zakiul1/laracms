<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /** Library UI */
    public function index()
    {
        // taxonomy is a COLUMN (not a relation)
        $categories = TermTaxonomy::with(['term', 'parent.term'])
            ->where('taxonomy', 'media_category')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();

        return view('admin.media.index', compact('categories'));
    }

    /** JSON: list/search/filter/paginate (defensive; no custom scopes required) */
    public function list(Request $request)
    {
        $query = Media::query();

        // Text search: title/filename/mime
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('filename', 'like', "%{$search}%")
                    ->orWhere('mime', 'like', "%{$search}%");
            });
        }

        // Images only?
        $imagesOnly = $request->boolean('images_only', true);
        if ($imagesOnly) {
            $query->where('mime', 'like', 'image/%');
        }

        // Category filter (by term_taxonomy_id)
        $ttId = (int) $request->query('term_taxonomy_id', 0);
        if ($ttId > 0) {
            // whereExists is robust and does not require a relation on the model
            $mediaTable = (new Media())->getTable();
            $trTable = (new TermRelationship())->getTable();

            $query->whereExists(function ($sub) use ($mediaTable, $trTable, $ttId) {
                $sub->select(DB::raw(1))
                    ->from($trTable)
                    ->whereColumn("{$trTable}.object_id", "{$mediaTable}.id")
                    ->where("{$trTable}.term_taxonomy_id", $ttId);
            });
        }

        // Sorting
        switch ($request->query('sort', 'newest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'largest':
                $query->orderByDesc('size')->orderByDesc('id');
                break;
            case 'smallest':
                $query->orderBy('size')->orderBy('id');
                break;
            default: // 'newest'
                $query->latest();
                break;
        }

        $items = $query->paginate(40);

        // Transform collection to attach thumb_url + category (without relying on relations)
        $items->getCollection()->transform(function (Media $m) {
            // Build URL defensively if no accessor exists
            try {
                $url = $m->url ?? null;
            } catch (\Throwable $e) {
                $url = null;
            }
            if (!$url && $m->disk && $m->path) {
                $url = Storage::disk($m->disk)->url($m->path);
            }
            $m->thumb_url = $url ?: '';

            // Attach 1st media_category for inspector (or null)
            $trTable = (new TermRelationship())->getTable();
            $ttTable = (new TermTaxonomy())->getTable();

            $ttId = TermRelationship::where("{$trTable}.object_id", $m->id)
                ->join($ttTable, "{$ttTable}.id", '=', "{$trTable}.term_taxonomy_id")
                ->where("{$ttTable}.taxonomy", 'media_category')
                ->value("{$ttTable}.id");

            $category = null;
            if ($ttId) {
                $category = TermTaxonomy::with('term')->find($ttId);
            }
            $m->category = $category;

            return $m;
        });

        return response()->json($items);
    }

    /** FilePond multi-upload */
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => ['required', 'file', 'max:20480'], // 20MB
            'term_taxonomy_id' => ['nullable', 'integer'],
        ]);

        $disk = config('filesystems.default', 'public');
        $added = [];

        foreach ((array) $request->file('files', []) as $file) {
            $path = $file->store('media/' . date('Y/m'), $disk);

            $media = Media::create([
                'created_by' => $request->user()->id ?? null,
                'disk' => $disk,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            if ($ttId = (int) $request->input('term_taxonomy_id')) {
                TermRelationship::firstOrCreate([
                    'object_id' => $media->id,
                    'term_taxonomy_id' => $ttId,
                ]);
            }

            $added[] = $media->fresh();
        }

        return response()->json(['status' => 'ok', 'items' => $added]);
    }

    /** Update name/alt/caption + optional attach category */
    public function updateMeta(Request $request, Media $media)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'term_taxonomy_id' => ['nullable', 'integer'],
        ]);

        if (array_key_exists('name', $data))
            $media->title = $data['name'];
        if (array_key_exists('alt', $data))
            $media->alt = $data['alt'];
        if (array_key_exists('caption', $data))
            $media->caption = $data['caption'];
        $media->save();

        if (!empty($data['term_taxonomy_id'])) {
            TermRelationship::firstOrCreate([
                'object_id' => $media->id,
                'term_taxonomy_id' => (int) $data['term_taxonomy_id'],
            ]);
        }

        return response()->json(['status' => 'ok', 'item' => $media->fresh()]);
    }

    /** Replace all categories in media_category with the given one */
    public function moveCategory(Request $request, Media $media)
    {
        $data = $request->validate([
            'term_taxonomy_id' => ['required', 'integer'],
        ]);

        $tr = (new TermRelationship())->getTable();
        $tt = (new TermTaxonomy())->getTable();

        TermRelationship::where("{$tr}.object_id", $media->id)
            ->whereIn("{$tr}.term_taxonomy_id", function ($q) use ($tt) {
                $q->select('id')->from($tt)->where('taxonomy', 'media_category');
            })
            ->delete();

        TermRelationship::firstOrCreate([
            'object_id' => $media->id,
            'term_taxonomy_id' => (int) $data['term_taxonomy_id'],
        ]);

        return response()->json(['status' => 'ok']);
    }

    /** Replace file */
    public function replaceFile(Request $request, Media $media)
    {
        $request->validate(['files.0' => ['required', 'file', 'max:20480']]);
        $file = $request->file('files.0');

        if ($media->path) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->path = $file->store('media/' . date('Y/m'), $media->disk);
        $media->filename = $file->getClientOriginalName();
        $media->mime = $file->getClientMimeType();
        $media->size = $file->getSize();
        $media->save();

        return response()->json(['status' => 'ok', 'item' => $media->fresh()]);
    }

    public function destroy(Media $media)
    {
        $media->delete();
        return response()->json(['status' => 'ok']);
    }

    public function restore($id)
    {
        $media = Media::onlyTrashed()->findOrFail($id);
        $media->restore();
        return response()->json(['status' => 'ok', 'item' => $media->fresh()]);
    }

    public function forceDelete($id)
    {
        $media = Media::withTrashed()->findOrFail($id);

        if ($media->path) {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->forceDelete();
        return response()->json(['status' => 'ok']);
    }
}