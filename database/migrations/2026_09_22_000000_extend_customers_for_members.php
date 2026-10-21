<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('member_code', 20)->nullable()->unique();
            $table->string('gender', 1)->nullable();
            $table->string('occupation', 150)->nullable();
            $table->text('address')->nullable();
            $table->date('resignation_requested_at')->nullable();
            $table->date('resigned_at')->nullable();
            $table->text('exit_reason')->nullable();
            $table->index(['status', 'member_code']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['status', 'member_code']);
            $table->dropUnique(['member_code']);
            $table->dropColumn([
                'member_code',
                'gender',
                'occupation',
                'address',
                'resignation_requested_at',
                'resigned_at',
                'exit_reason',
            ]);
        });
    }
};