<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Models\Module;


class ModuleDeactivate extends Command
{
    protected $signature = 'module:deactivate {slug}';
    protected $description = 'Deactivate a module';


    public function handle(): int
    {
        $slug = strtolower($this->argument('slug'));
        Module::query()->where('slug', $slug)->update(['is_active' => false]);
        $this->info("Deactivated: {$slug}");
        return 0;
    }
}