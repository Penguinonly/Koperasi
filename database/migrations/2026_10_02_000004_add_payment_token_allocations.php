<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_tokens', function (Blueprint $table) {
            $table->string('key_id', 80)->default('server-2026-01');
            $table->unsignedSmallInteger('payload_version')->default(1);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('payment_token_id')
                ->nullable()
                ->unique()
                ->constrained('payment_tokens')
                ->nullOnDelete();
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_schedule_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['payment_id', 'loan_schedule_id']);
            $table->index('loan_schedule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_token_id']);
            $table->dropUnique(['payment_token_id']);
            $table->dropColumn('payment_token_id');
        });

        Schema::table('payment_tokens', function (Blueprint $table) {
            $table->dropColumn(['key_id', 'payload_version']);
        });
    }
};