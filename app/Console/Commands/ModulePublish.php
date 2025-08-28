<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Support\AssetPublisher;


class ModulePublish extends Command
{
    protected $signature = 'module:publish {slug?} {--all}';
    protected $description = 'Publish module /dist → public/modules/{slug}';


    public function handle(): int
    {
        $base = config('laracms.modules_path');
        $pub = config('laracms.public_modules_path');
        $publisher = app(AssetPublisher::class);


        $targets = [];
        if ($this->option('all')) {
            foreach (scandir($base) as $d)
                if ($d !== '.' && $d !== '..')
                    $targets[] = $d;
        } else {
            $slug = $this->argument('slug');
            if (!$slug) {
                $this->error('Provide {slug} or use --all');
                return 1;
            }
            $targets[] = $slug;
        }


        foreach ($targets as $slug) {
            $ok = $publisher->mirror($base . DIRECTORY_SEPARATOR . $slug . '/dist', $pub . DIRECTORY_SEPARATOR . strtolower($slug));
            $ok ? $this->info("Published: {$slug}") : $this->warn("No dist for: {$slug}");
        }
        return 0;
    }
}