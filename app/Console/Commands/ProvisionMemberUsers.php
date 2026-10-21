<?php

namespace App\Console\Commands;

use App\Services\MemberAccountProvisioner;
use Illuminate\Console\Command;

class ProvisionMemberUsers extends Command
{
    protected $signature = 'members:provision-users {--password=password : Initial password for provisioned accounts}';

    protected $description = 'Create customer login accounts for members without users';

    public function handle(MemberAccountProvisioner $provisioner): int
    {
        $created = $provisioner->provision($this->option('password'));
        $this->info("Provisioned {$created} member accounts.");

        return self::SUCCESS;
    }
}