<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employee_loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained('employee_loans')->onDelete('cascade');
            $table->date('repayment_month'); // e.g., 2025-08-01
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'skipped'])->default('pending');
            $table->text('remarks')->nullable();
            $table->boolean('is_final')->default(false); // for foreclosure
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_repayments');
    }
};
