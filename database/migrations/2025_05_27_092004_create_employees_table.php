<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->unique()->constrained()->onDelete('set null');
            $table->boolean('active')->default(true);
            $table->text('address')->nullable();
            $table->string('anekk_id')->nullable();
            $table->decimal('average_salary', 10, 2);
            $table->timestamp('block_until')->nullable();
            $table->integer('daily_attempts')->default(0);
            $table->date('date_of_joining')->nullable();
            $table->string('employee_id')->nullable();
            $table->integer('failed_attempts')->default(0);
            $table->string('fcm_token')->nullable();
            $table->string('first_name')->nullable();
            $table->string('gender')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->string('job_location')->nullable();
            $table->string('kyc_status');
            $table->date('last_attempt_date');
            $table->decimal('last_month_before_take_home_salary', 10, 2);
            $table->decimal('last_month_take_home_salary', 10, 2);
            $table->string('last_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('official_email')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
