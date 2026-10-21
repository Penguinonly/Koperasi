<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $customers = DB::table('customers')->whereNotNull('member_code')->get(['id', 'member_code']);

        foreach ($customers as $customer) {
            $normalized = (string) ((int) $customer->member_code);

            if ($normalized !== $customer->member_code) {
                DB::table('customers')->where('id', $customer->id)->update(['member_code' => $normalized]);
            }
        }
    }

    public function down(): void
    {
        // Member codes intentionally remain in their normalized form after rollback.
    }
};