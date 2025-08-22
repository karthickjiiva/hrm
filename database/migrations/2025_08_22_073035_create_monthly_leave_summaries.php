<?php

 // database/migrations/2025_08_22_000000_create_monthly_leave_summaries.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('monthly_leave_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedTinyInteger('month'); // 1-12
            $table->unsignedSmallInteger('year'); // e.g. 2025

            // Opening balances at start of month (BEFORE accrual)
            $table->decimal('opening_cl', 8, 2)->default(0);
            $table->decimal('opening_sl', 8, 2)->default(0);
            $table->decimal('opening_el', 8, 2)->default(0);

            // Accrual (earned this month)
            $table->decimal('earned_cl', 8, 2)->default(0);
            $table->decimal('earned_sl', 8, 2)->default(0);
            $table->decimal('earned_el', 8, 2)->default(0);

            // Availed (consumed this month by type, AFTER sandwich/half-day rules)
            $table->decimal('availed_cl', 8, 2)->default(0);
            $table->decimal('availed_sl', 8, 2)->default(0);
            $table->decimal('availed_el', 8, 2)->default(0);

            // Closing balances at month end
            $table->decimal('closing_cl', 8, 2)->default(0);
            $table->decimal('closing_sl', 8, 2)->default(0);
            $table->decimal('closing_el', 8, 2)->default(0);

            // Totals shown in your last 3 columns
            $table->decimal('total_availed', 8, 2)->default(0);
            $table->decimal('closing_balance_total', 8, 2)->default(0);
            $table->decimal('lop_days', 8, 2)->default(0);

            // Optional meta for audits
            $table->unsignedInteger('sandwich_days')->default(0);
            $table->unsignedInteger('half_days')->default(0);

            // Payroll context (handy for cross-checks)
            $table->unsignedTinyInteger('total_working_days')->default(0);
            $table->unsignedTinyInteger('days_payable')->default(0);
            $table->unsignedTinyInteger('loss_of_pay_days')->default(0);

            $table->timestamps();
            $table->unique(['employee_id', 'month', 'year']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('monthly_leave_summaries');
    }
};
