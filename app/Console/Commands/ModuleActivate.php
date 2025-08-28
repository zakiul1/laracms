<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\Module;


class ModuleActivate extends Command
{
    protected $signature = 'module:activate {slug}';
    protected $description = 'Activate a module (DB: modules.is_active = 1)';


    public function handle(): int
    {
        $slug = strtolower($this->argument('slug'));
        $ok = Module::query()->where('slug', $slug)->update(['is_active' => true]);
        if (!$ok)
            Module::create(['slug' => $slug, 'name' => $slug, 'version' => '1.0.0', 'is_active' => true]);
        $this->info("Activated: {$slug}");
        return 0;
    }
}