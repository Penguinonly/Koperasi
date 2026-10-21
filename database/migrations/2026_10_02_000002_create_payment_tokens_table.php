<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menggantikan payment_qrs lama. Token tidak lagi membawa payload
     * alokasi (scheduleIds / type ONE_DAY-TWO_DAYS-dst) — alokasi ke
     * jadwal tetap dihitung server saat konfirmasi. Token di sini hanya
     * membuktikan "serah-terima klaim yang sah dan belum pernah dipakai",
     * lewat kombinasi (loan_id, sequence) yang unik dan tanda tangan
     * Ed25519 yang bisa diverifikasi offline oleh app nasabah.
     */
    public function up(): void
    {
        Schema::create('payment_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('token_uuid')->unique();
            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->foreignId('issued_by')->constrained('users');
            $table->decimal('max_amount', 12, 2);
            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->text('signature');
            $table->enum('status', [
                'ISSUED',
                'REDEEMED_OFFLINE',
                'CONFIRMED',
                'EXPIRED',
                'VOID',
            ])->default('ISSUED');
            // Device time is audit metadata; server time is authoritative.
            $table->timestamp('redeemed_offline_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->decimal('confirmed_amount', 12, 2)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'sequence'], 'uniq_loan_sequence');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_tokens');
    }
};