<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->index();
            $table->string('platform')->default('WEB')->index();
            $table->string('employee_id')->nullable()->unique();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('business_name')->nullable();
            $table->json('business_location')->nullable();
            $table->string('id_number', 80)->nullable()->unique();
            $table->string('id_type', 20)->default('KTP');
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->unsignedInteger('streak_count')->default(0);
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collector_id')->constrained('users');
            $table->decimal('loan_amount', 12, 2);
            $table->unsignedSmallInteger('duration_days')->default(60);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->string('status', 40)->default('PENDING_APPROVAL')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approval_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'status']);
            $table->index(['collector_id', 'status']);
        });

        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->date('due_date')->index();
            $table->decimal('amount_due', 12, 2);
            $table->string('status', 20)->default('UNPAID')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['loan_id', 'day_number']);
            $table->unique(['loan_id', 'due_date']);
        });

        Schema::create('payment_qrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collector_id')->constrained('users');
            $table->string('type', 40);
            $table->decimal('amount', 12, 2);
            $table->text('qr_signature')->nullable();
            $table->string('status', 20)->default('ACTIVE')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('payment_qr_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_qr_id')->constrained('payment_qrs')->cascadeOnDelete();
            $table->foreignId('loan_schedule_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['payment_qr_id', 'loan_schedule_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30)->default('QR_CODE');
            $table->foreignId('payment_qr_id')->nullable()->constrained('payment_qrs')->nullOnDelete();
            $table->string('status', 20)->default('PENDING')->index();
            $table->string('reference_code', 120)->unique();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_code', 80)->index();
            $table->string('fingerprint_template_id', 80);
            $table->string('employee_id', 80)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->boolean('accepted')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date')->index();
            $table->timestamp('morning_in')->nullable();
            $table->timestamp('morning_out')->nullable();
            $table->timestamp('afternoon_in')->nullable();
            $table->timestamp('afternoon_out')->nullable();
            $table->string('status', 20)->default('ABSENT')->index();
            $table->string('calendar_color', 20)->default('RED');
            $table->timestamps();
            $table->unique(['user_id', 'attendance_date']);
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('PENDING')->index();
            $table->text('notes')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('approval_date')->nullable()->index();
            $table->timestamps();
            $table->unique('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_qr_schedules');
        Schema::dropIfExists('payment_qrs');
        Schema::dropIfExists('loan_schedules');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('customers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role_id', 'platform', 'employee_id', 'is_active']);
        });

        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
