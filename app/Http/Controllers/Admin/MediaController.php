<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /** Library UI (page) */
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

    /**
     * JSON: list/search/filter/paginate
     * Supports:
     *  - q: string search
     *  - type: image|video|audio|doc|all (fallback: images_only=true)
     *  - term_taxonomy_id: filter by media_category (TT id)
     *  - sort: newest|oldest|name|largest|smallest
     *  - page, per_page
     *
     * Returns shape:
     * { data: [...], meta: { current_page, last_page, per_page, total } }
     */
    public function list(Request $request)
    {
        $query = Media::query();

        // Text search
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('filename', 'like', "%{$search}%")
                    ->orWhere('mime', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%");
            });
        }

        // Type filter (image|video|audio|doc|all) — falls back to images_only=true
        $type = $request->query('type');
        if ($type && $type !== 'all') {
            $query->where(function ($w) use ($type) {
                if ($type === 'image') {
                    $w->where('mime', 'like', 'image/%');
                } elseif ($type === 'video') {
                    $w->where('mime', 'like', 'video/%');
                } elseif ($type === 'audio') {
                    $w->where('mime', 'like', 'audio/%');
                } elseif ($type === 'doc') {
                    $w->where(function ($q) {
                        $q->where('mime', 'like', 'application/%')
                            ->orWhere('mime', 'like', 'text/%')
                            ->orWhere('mime', 'like', 'model/%'); // pdf/office/etc
                    });
                }
            });
        } else {
            // Back-compat: images_only defaults true in your old UI
            if ($request->has('images_only')) {
                if ($request->boolean('images_only')) {
                    $query->where('mime', 'like', 'image/%');
                }
            } else {
                // default true (your previous behavior)
                $query->where('mime', 'like', 'image/%');
            }
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
        $perPage = max(10, min($perPage, 100));

        $paginator = $query->paginate($perPage)->appends($request->query());

        // Map items to browser-friendly shape
        $data = [];
        foreach ($paginator->items() as $m) {
            $data[] = $this->formatMedia($m);
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /** JSON: single item details for sidebar */
    public function show(Media $media)
    {
        return response()->json($this->formatMedia($media));
    }

    /** Multi-upload (drag & drop / picker) */
    public function upload(Request $request)
    {
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'max:20480'], // 20MB
            'term_taxonomy_id' => ['nullable', 'integer'],
        ]);

        $disk = 'public';
        $dir = 'media/' . now()->format('Y/m');

        $added = [];
        foreach ((array) $request->file('files', []) as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $path = $file->store($dir, $disk);

            $media = new Media();
            $media->created_by = $request->user()->id ?? null;
            $media->disk = $disk;
            $media->path = $path;
            $media->filename = $file->getClientOriginalName();
            $media->mime = $file->getMimeType() ?: $file->getClientMimeType();
            $media->size = $file->getSize();
            $media->title = pathinfo($media->filename, PATHINFO_FILENAME);
            $media->alt = null;
            $media->caption = null;

            // Try to capture dimensions for images
            try {
                if (Str::startsWith($media->mime, 'image/')) {
                    [$w, $h] = @getimagesize($file->getRealPath()) ?: [null, null];
                    $media->width = $w;
                    $media->height = $h;
                }
            } catch (\Throwable $e) {
                // ignore dimension failures
            }

            $media->save();

            if ($ttId = (int) $request->input('term_taxonomy_id', 0)) {
                TermRelationship::updateOrCreate(
                    ['object_id' => $media->id, 'term_taxonomy_id' => $ttId],
                    ['sort_order' => 0]
                );
            }

            $added[] = $media->fresh();
        }

        // For the new modal:
        $uploaded = array_map(fn($m) => $this->formatMedia($m), $added);

        // Keep your old shape for back-compat
        return response()->json([
            'status' => 'ok',
            'items' => $added,   // old
            'uploaded' => $uploaded // new
        ]);
    }

    /** Update meta (title/name, alt, caption, description if present) */
    public function updateMeta(Request $request, Media $media)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'], // back-compat
            'alt' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string'],            // optional column
            'term_taxonomy_id' => ['nullable', 'integer'],
        ]);

        $title = $data['title'] ?? $data['name'] ?? null;
        if ($title !== null) {
            $media->title = $title;
        }
        if (array_key_exists('alt', $data)) {
            $media->alt = $data['alt'];
        }
        if (array_key_exists('caption', $data)) {
            $media->caption = $data['caption'];
        }

        // Only set description if the column exists
        if (array_key_exists('description', $data) && $this->mediaHasColumn('description')) {
            $media->description = $data['description'];
        }

        $media->save();

        // Move category if passed
        if ($request->filled('term_taxonomy_id')) {
            $ttId = (int) $request->input('term_taxonomy_id');
            $media->moveToCategoryByTT($ttId);
        }

        return response()->json([
            'status' => 'ok',
            'item' => $this->formatMedia($media->fresh()),
        ]);
    }

    public function moveCategory(Request $request, Media $media)
    {
        $ttId = (int) $request->validate([
            'term_taxonomy_id' => ['required', 'integer'],
        ])['term_taxonomy_id'];

        $media->moveToCategoryByTT($ttId);

        return response()->json([
            'status' => 'ok',
            'item' => $this->formatMedia($media->fresh()),
        ]);
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

        // try dimensions for images
        try {
            if (Str::startsWith($media->mime, 'image/')) {
                [$w, $h] = @getimagesize($file->getRealPath()) ?: [null, null];
                $media->width = $w;
                $media->height = $h;
            } else {
                $media->width = $media->height = null;
            }
        } catch (\Throwable $e) {
        }

        $media->save();

        return response()->json([
            'status' => 'ok',
            'item' => $this->formatMedia($media->fresh()),
        ]);
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
        return response()->json([
            'status' => 'ok',
            'item' => $this->formatMedia($media->fresh()),
        ]);
    }

    /** Single-item permanent delete */
    public function forceDelete($id)
    {
        $media = Media::withTrashed()->findOrFail($id);

        if ($media->path) {
            try {
                Storage::disk($media->disk ?: 'public')->delete($media->path);
            } catch (\Throwable $e) {
                // ignore file delete errors
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

    /* ===================== Helpers ===================== */

    /** Ensure the outgoing JSON shape is consistent for the modal */
    protected function formatMedia(Media $m): array
    {
        // Build public URL (try disk url, fallback to /storage)
        $disk = $m->disk ?: 'public';
        $url = '';
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

        $category = $firstTtId ? TermTaxonomy::with('term')->find($firstTtId) : null;

        return [
            'id' => $m->id,
            'url' => $url,
            'thumb' => $url, // add real thumb variant if you generate one
            'mime' => $m->mime,
            'type' => Str::before($m->mime ?? '', '/'),
            'filename' => $m->filename,
            'size' => (int) ($m->size ?? 0),
            'width' => $m->width,
            'height' => $m->height,
            'title' => $m->title,
            'alt' => $m->alt,
            'caption' => $m->caption,
            'description' => $this->mediaHasColumn('description') ? ($m->description ?? null) : null,
            'exists' => (bool) $exists,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->term?->name,
                'slug' => $category->term?->slug,
            ] : null,
            'created_at' => optional($m->created_at)->toDateTimeString(),
        ];
    }

    protected function mediaHasColumn(string $column): bool
    {
        static $cache = [];
        if (!array_key_exists($column, $cache)) {
            try {
                $cache[$column] = Schema::hasColumn((new Media())->getTable(), $column);
            } catch (\Throwable $e) {
                $cache[$column] = false;
            }
        }
        return $cache[$column];
    }
}