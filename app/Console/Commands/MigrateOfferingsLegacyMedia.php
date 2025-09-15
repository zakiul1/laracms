<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;

class MigrateOfferingsLegacyMedia extends Command
{
    protected $signature = 'media:migrate-offerings {--dry-run}';
    protected $description = 'Attach legacy featured images to Spatie featured collection for Offering (product) posts';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        $posts = Post::query()
            ->where('type', 'post')
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('term_relationships as tr')
                    ->join('term_taxonomies as tt', 'tt.id', '=', 'tr.term_taxonomy_id')
                    ->join('terms as t', 't.id', '=', 'tt.term_id')
                    ->whereColumn('tr.object_id', 'posts.id')
                    ->where('tt.taxonomy', 'category')
                    ->where('t.slug', 'product');
            })
            ->with(['featuredMedia', 'gallery', 'media'])
            ->get();

        $this->info("Found {$posts->count()} offering posts.");

        $migrated = 0;
        foreach ($posts as $post) {
            // Skip if already have Spatie featured
            if ($post->getFirstMedia('featured')) {
                $this->line("• #{$post->id} already has Spatie featured → skip");
                continue;
            }

            // Pick legacy record (your same logic)
            $legacy = $post->featuredMedia ?? ($post->gallery->first() ?? null);
            if (!$legacy) {
                $this->warn("• #{$post->id} has no legacy media");
                continue;
            }

            $disk = $legacy->disk ?? null;
            $candidates = array_filter([
                $legacy->url ?? null,
                $legacy->path ?? null,
                $legacy->file_path ?? null,
                $legacy->filepath ?? null,
                isset($legacy->dir, $legacy->filename) ? trim($legacy->dir, '/') . '/' . $legacy->filename : null,
            ]);

            $sourcePath = null;
            $sourceDisk = null;

            foreach ($candidates as $cand) {
                $cand = ltrim((string) $cand, '/');

                // Absolute HTTP(S) — download to temp
                if (preg_match('~^https?://~i', $cand)) {
                    $tmp = storage_path('app/tmp');
                    if (!is_dir($tmp))
                        @mkdir($tmp, 0775, true);
                    $tmpFile = $tmp . '/' . basename(parse_url($cand, PHP_URL_PATH) ?: uniqid('img_'));
                    try {
                        $data = @file_get_contents($cand);
                        if ($data !== false) {
                            file_put_contents($tmpFile, $data);
                            $sourcePath = $tmpFile;
                            break;
                        }
                    } catch (\Throwable $e) {
                    }
                }

                // Disk-specific path
                if ($disk && Storage::disk($disk)->exists($cand)) {
                    $sourcePath = Storage::disk($disk)->path($cand);
                    $sourceDisk = $disk;
                    break;
                }

                // Common disks
                foreach (['public', 'local', 's3'] as $try) {
                    if (Storage::disk($try)->exists($cand)) {
                        $sourcePath = Storage::disk($try)->path($cand);
                        $sourceDisk = $try;
                        break 2;
                    }
                }

                // Public path
                if (is_file(public_path($cand))) {
                    $sourcePath = public_path($cand);
                    break;
                }
            }

            if (!$sourcePath || !is_file($sourcePath)) {
                $this->warn("• #{$post->id} legacy file not found");
                continue;
            }

            $this->line("• #{$post->id} attach => {$sourcePath}" . ($dry ? " [DRY]" : ""));

            if ($dry)
                continue;

            try {
                $media = $post->addMedia($sourcePath)
                    ->usingFileName(basename($sourcePath))
                    ->withResponsiveImages()             // enables Spatie-native srcset for original
                    ->preservingOriginal()
                    ->toMediaCollection('featured');

                // Optional: set alt from title
                $media->setCustomProperty('alt', $post->title);
                $media->save();

                $migrated++;
            } catch (\Throwable $e) {
                $this->error("  !! failed: {$e->getMessage()}");
            }
        }

        $this->info("Done. Migrated {$migrated} featured images.");
        $this->info("Tip: run `php artisan media-library:regenerate \"App\\Models\\Post\"` if you changed conversions.");
        return self::SUCCESS;
    }
}