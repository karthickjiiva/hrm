<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_leave_masters', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id')->unique();
            $table->decimal('sl', 8, 2)->default(0); // Sick Leave
            $table->decimal('cl', 8, 2)->default(0); // Casual Leave
            $table->decimal('el', 8, 2)->default(0); // Earned Leave

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_masters');
    }
};
