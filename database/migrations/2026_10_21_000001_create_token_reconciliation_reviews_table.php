<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_reconciliation_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('token_sync_log_id')->index();
            $table->uuid('token_uuid')->index();
            $table->foreignId('reviewed_by')->constrained('users');
            $table->enum('resolution', ['REVIEWED', 'FOLLOW_UP', 'NO_ACTION']);
            $table->text('notes');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['token_sync_log_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_reconciliation_reviews');
    }
};
