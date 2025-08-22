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
    Schema::create('bank_statements', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('employee_id');
    $table->unsignedInteger('month');
    $table->unsignedInteger('year');
    $table->string('remark')->nullable();
    $table->decimal('amount', 12, 2);
    $table->timestamps();

    $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
