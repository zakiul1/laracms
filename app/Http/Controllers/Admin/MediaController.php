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
        $categories = TermTaxonomy::mediaCategory()
            ->with('term')
            ->orderBy('term_id')
            ->get()
            ->map(function ($tt) {
                return (object) [
                    'id' => $tt->id,
                    'name' => $tt->term?->name ?? '—',
                    'slug' => $tt->term?->slug ?? null,
                ];
            });

        return view('admin.media.index', compact('categories'));
    }

    /** JSON: list/search/filter/paginate */
    public function list(Request $request)
    {
        $query = Media::query();

        // Text search
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('filename', 'like', "%{$search}%")
                    ->orWhere('mime', 'like', "%{$search}%");
            });
        }

        // Images only?
        if ($request->boolean('images_only', true)) {
            $query->where('mime', 'like', 'image/%');
        }

        // Category filter (by term_taxonomy_id)
        $ttId = (int) $request->query('term_taxonomy_id', 0);
        if ($ttId > 0) {
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
                $query->orderBy('id', 'asc');
                break;
            case 'name':
                $query->orderBy('title')->orderBy('filename');
                break;
            case 'largest':
                $query->orderBy('size', 'desc')->orderBy('id', 'desc');
                break;
            case 'smallest':
                $query->orderBy('size', 'asc')->orderBy('id', 'desc');
                break;
            default: // 'newest'
                $query->orderBy('id', 'desc');
                break;
        }

        // Pagination controls (?per_page=)
        $perPage = (int) $request->integer('per_page', 40);
        $perPage = max(10, min($perPage, 100)); // clamp 10..100

        $items = $query->paginate($perPage);

        // Attach thumb_url + exists flag + first media_category
        $items->getCollection()->transform(function (Media $m) {
            $disk = $m->disk ?: 'public';
            $url = null;
            $exists = false;

            if ($m->path) {
                try {
                    $exists = Storage::disk($disk)->exists($m->path);
                    if ($exists) {
                        try {
                            $url = Storage::disk($disk)->url($m->path);
                        } catch (\Throwable $e) {
                            $url = asset('storage/' . ltrim($m->path, '/'));
                        }
                    } else {
                        if ($disk !== 'public' && Storage::disk('public')->exists($m->path)) {
                            $exists = true;
                            $url = asset('storage/' . ltrim($m->path, '/'));
                        }
                    }
                } catch (\Throwable $e) {
                    $url = asset('storage/' . ltrim($m->path, '/'));
                }
            }

            $m->thumb_url = $url ?: '';
            $m->exists = $exists;

            // First media_category (with term)
            $trTable = (new TermRelationship())->getTable();
            $ttTable = (new TermTaxonomy())->getTable();

            $firstTtId = TermRelationship::query()
                ->select("{$ttTable}.id")
                ->join($ttTable, "{$ttTable}.id", '=', "{$trTable}.term_taxonomy_id")
                ->where("{$trTable}.object_id", $m->id)
                ->where("{$ttTable}.taxonomy", 'media_category')
                ->orderBy("{$trTable}.sort_order")
                ->value("{$ttTable}.id");

            $m->category = $firstTtId ? TermTaxonomy::with('term')->find($firstTtId) : null;

            return $m;
        });

        return response()->json($items);
    }

    /** Multi-upload */
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => ['required', 'file', 'max:20480'], // 20MB
            'term_taxonomy_id' => ['nullable', 'integer'],
        ]);

        $disk = 'public';
        $dir = 'media/' . now()->format('Y/m');

        $added = [];

        foreach ((array) $request->file('files', []) as $file) {
            $path = $file->store($dir, $disk);

            $media = Media::create([
                'created_by' => $request->user()->id ?? null,
                'disk' => $disk,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: $file->getClientMimeType(),
                'size' => $file->getSize(),
                'width' => null,
                'height' => null,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'alt' => null,
                'caption' => null,
            ]);

            if ($ttId = (int) $request->input('term_taxonomy_id', 0)) {
                TermRelationship::updateOrCreate(
                    ['object_id' => $media->id, 'term_taxonomy_id' => $ttId],
                    ['sort_order' => 0]
                );
            }

            $added[] = $media->fresh();
        }

        return response()->json(['status' => 'ok', 'items' => $added]);
    }

    public function updateMeta(Request $request, Media $media)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
        ]);

        $media->title = $data['name'] ?? $media->title;
        $media->alt = $data['alt'] ?? $media->alt;
        $media->caption = $data['caption'] ?? $media->caption;
        $media->save();

        // Move category if passed
        if ($request->filled('term_taxonomy_id')) {
            $ttId = (int) $request->input('term_taxonomy_id');
            $media->moveToCategoryByTT($ttId);
        }

        return response()->json(['status' => 'ok', 'item' => $media->fresh()]);
    }

    public function moveCategory(Request $request, Media $media)
    {
        $ttId = (int) $request->validate([
            'term_taxonomy_id' => ['required', 'integer'],
        ])['term_taxonomy_id'];

        $media->moveToCategoryByTT($ttId);

        return response()->json(['status' => 'ok', 'item' => $media->fresh()]);
    }

    /** Hard replace the binary, keep metadata */
    public function replaceFile(Request $request, Media $media)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $disk = 'public';
        $file = $request->file('file');

        if ($media->path && $media->disk) {
            try {
                Storage::disk($media->disk)->delete($media->path);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $path = $file->store('media/' . now()->format('Y/m'), $disk);

        $media->disk = $disk;
        $media->path = $path;
        $media->filename = $file->getClientOriginalName();
        $media->mime = $file->getMimeType() ?: $file->getClientMimeType();
        $media->size = $file->getSize();
        $media->save();

        return response()->json(['status' => 'ok', 'item' => $media->fresh()]);
    }

    /** Single-item soft delete */
    public function destroy(Media $media)
    {
        $media->delete();
        return response()->json(['status' => 'ok']);
    }

    /** Single-item restore */
    public function restore($id)
    {
        $media = Media::onlyTrashed()->findOrFail($id);
        $media->restore();
        return response()->json(['status' => 'ok', 'item' => $media->fresh()]);
    }

    /** Single-item permanent delete */
    public function forceDelete($id)
    {
        $media = Media::withTrashed()->findOrFail($id);

        if ($media->path) {
            try {
                Storage::disk($media->disk ?: 'public')->delete($media->path);
            } catch (\Throwable $e) {
                // ignore delete errors
            }
        }

        $media->forceDelete();
        return response()->json(['status' => 'ok']);
    }

    /* ===================== BULK ACTIONS ===================== */

    /** Bulk soft-delete (move to trash) */
    public function bulkDelete(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->map(fn($v) => (int) $v)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['status' => 'ok', 'deleted' => 0]);
        }

        $deleted = Media::whereIn('id', $ids)->delete();

        return response()->json(['status' => 'ok', 'deleted' => $deleted]);
    }

    /** Bulk restore from trash */
    public function bulkRestore(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->map(fn($v) => (int) $v)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['status' => 'ok', 'restored' => 0]);
        }

        $restored = Media::onlyTrashed()->whereIn('id', $ids)->restore();

        return response()->json(['status' => 'ok', 'restored' => $restored]);
    }

    /** Bulk permanent delete (also delete files) */
    public function bulkForceDelete(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->map(fn($v) => (int) $v)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['status' => 'ok', 'deleted' => 0]);
        }

        $deleted = 0;

        Media::withTrashed()->whereIn('id', $ids)->chunkById(100, function ($rows) use (&$deleted) {
            foreach ($rows as $media) {
                try {
                    if ($media->path) {
                        Storage::disk($media->disk ?: 'public')->delete($media->path);
                    }
                } catch (\Throwable $e) {
                    // ignore file delete errors
                }
                $media->forceDelete();
                $deleted++;
            }
        });

        return response()->json(['status' => 'ok', 'deleted' => $deleted]);
    }
}