<?php

namespace Database\Seeders;

use App\Services\MemberCsvImporter;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/members.csv');

        if (is_file($path)) {
            $this->command?->info('Imported '.app(MemberCsvImporter::class)->import($path).' members.');
        }
    }
}