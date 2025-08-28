<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        Module::updateOrCreate(['slug' => 'hello'], [
            'name' => 'Hello',
            'version' => '1.0.0',
            'is_active' => true,
            'meta' => [],
        ]);
    }
}