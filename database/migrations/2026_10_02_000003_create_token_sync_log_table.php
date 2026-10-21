<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
    * Jejak append-only untuk setiap percobaan sinkronisasi. Log tidak
    * memiliki foreign key ke payment_tokens agar token tidak valid tetap
    * dapat dicatat sebagai bukti audit.
     */
    public function up(): void
    {
        Schema::create('token_sync_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('token_uuid');
            $table->string('submitted_by_device', 100)->nullable();
            $table->timestamp('submitted_at');
            $table->enum('result', [
                'ACCEPTED',
                'DUPLICATE',
                'SEQUENCE_CONFLICT',
                'EXPIRED',
                'SIGNATURE_INVALID',
            ]);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('token_uuid');
            $table->index('result');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_sync_log');
    }
};