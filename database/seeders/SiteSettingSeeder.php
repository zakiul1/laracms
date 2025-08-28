<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::firstOrCreate([], [
            'site_name' => 'laracms',
            'options' => ['footer_text' => '© ' . date('Y') . ' laracms'],
        ]);
    }
}