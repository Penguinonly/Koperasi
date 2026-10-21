<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu baris per pinjaman. Menyimpan nomor urut token terakhir
     * yang DIKELUARKAN server (last_issued_sequence) dan nomor urut
     * terakhir yang sudah DIKONFIRMASI lewat sinkronisasi
     * (last_confirmed_sequence). Server adalah satu-satunya pihak
     * yang boleh menaikkan last_issued_sequence — kolektor tidak
     * pernah membuat nomor urut sendiri.
     */
    public function up(): void
    {
        Schema::create('loan_token_counters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')
                ->unique()
                ->constrained('loans')
                ->cascadeOnDelete();

            $table->unsignedInteger('last_issued_sequence')->default(0);
            $table->unsignedInteger('last_confirmed_sequence')->default(0);

            $table->timestamps();

            // last_confirmed_sequence <= last_issued_sequence is enforced in the service layer.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_token_counters');
    }
};