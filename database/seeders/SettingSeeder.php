<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::firstOrCreate(['key' => 'ho_limit_days'], ['value' => '1']);
        Setting::firstOrCreate(['key' => 'ho_limit_period'], ['value' => '14']);
    }
}
