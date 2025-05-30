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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('loan_number')->unique();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('term_months');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'disbursed',
                'active',
                'completed',
                'defaulted'
            ])->default('pending');
            $table->text('purpose');
            $table->text('collateral')->nullable();
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_contact')->nullable();
            $table->string('guarantor_relationship')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('disbursed_by')->nullable()->constrained('users');
            $table->timestamp('disbursed_at')->nullable();
            $table->string('disbursement_method')->nullable();
            $table->json('disbursement_details')->nullable();
            $table->json('documents')->nullable();
            $table->text('remarks')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['employee_id', 'status']);
            $table->index('loan_number');
            $table->index('type');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
}; 