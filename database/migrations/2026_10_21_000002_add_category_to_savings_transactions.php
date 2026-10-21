<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->string('category', 20)->nullable()->after('type');
            $table->index(['customer_id', 'category', 'id'], 'savings_transactions_customer_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('savings_transactions', function (Blueprint $table) {
            $table->dropIndex('savings_transactions_customer_category_id_index');
            $table->dropColumn('category');
        });
    }
};
