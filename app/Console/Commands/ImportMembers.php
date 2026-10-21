<?php

namespace App\Console\Commands;

use App\Services\MemberCsvImporter;
use Illuminate\Console\Command;

class ImportMembers extends Command
{
    protected $signature = 'members:import {path=database/data/members.csv}';

    protected $description = 'Import cooperative members from a CSV file';

    public function handle(MemberCsvImporter $importer): int
    {
        $pathArgument = $this->argument('path');
        $path = str_starts_with($pathArgument, DIRECTORY_SEPARATOR)
            ? $pathArgument
            : base_path($pathArgument);

        $this->info("Imported {$importer->import($path)} members.");

        return self::SUCCESS;
    }
}