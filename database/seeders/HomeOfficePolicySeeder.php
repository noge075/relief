<?php

namespace Database\Seeders;

use App\Enums\HomeOfficePolicyType;
use App\Models\HomeOfficePolicy;
use Illuminate\Database\Seeder;

class HomeOfficePolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HomeOfficePolicy::firstOrCreate(
            ['name' => 'Teljes HO'],
            [
                'description' => 'A munkavállaló teljes munkaidőben otthonról dolgozik.',
                'type' => HomeOfficePolicyType::FULL_REMOTE,
            ]
        );

        HomeOfficePolicy::firstOrCreate(
            ['name' => 'Rugalmas HO'],
            [
                'description' => 'A munkavállaló eseti jelleggel, korlátlanul igényelhet otthoni munkavégzést.',
                'type' => HomeOfficePolicyType::FLEXIBLE,
            ]
        );

        HomeOfficePolicy::firstOrCreate(
            ['name' => 'Szabályozott HO'],
            [
                'description' => 'A munkavállaló 14 napos periódusban 1 nap otthoni munkavégzésre jogosult.',
                'type' => HomeOfficePolicyType::LIMITED,
                'limit_days' => 1,
                'period_days' => 14,
            ]
        );

        HomeOfficePolicy::firstOrCreate(
            ['name' => 'Nincs HO'],
            [
                'description' => 'A munkavállaló nem jogosult otthoni munkavégzésre.',
                'type' => HomeOfficePolicyType::NONE,
            ]
        );
    }
}