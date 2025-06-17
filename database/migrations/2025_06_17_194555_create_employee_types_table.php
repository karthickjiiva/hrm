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
        Schema::create('employee_types', function (Blueprint $table) {
        $table->id();
        $table->string('type');
        $table->decimal('basic_percent', 8, 2)->default(0);
        $table->decimal('hra_percent', 8, 2)->default(0);
        $table->decimal('allowance_percent', 8, 2)->default(0);
        $table->decimal('food_allowance_percent', 8, 2)->default(0);

        $table->boolean('pf_enabled')->default(false);
        $table->decimal('pf_percentage', 8, 2)->nullable();
        $table->decimal('pf_limit', 10, 2)->nullable();

        $table->boolean('esi_enabled')->default(false);
        $table->decimal('esi_percentage', 8, 2)->nullable();
        $table->decimal('esi_limit', 10, 2)->nullable();

        $table->boolean('prof_tax_enabled')->default(false);
        $table->decimal('prof_tax_percentage', 8, 2)->nullable();
        $table->decimal('prof_tax_limit', 10, 2)->nullable();

        $table->boolean('tds_enabled')->default(false);
        $table->decimal('tds_percentage', 8, 2)->nullable();
        $table->decimal('tds_limit', 10, 2)->nullable();

        $table->string('status')->default('active');
        $table->unsignedBigInteger('created_by');
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_types');
    }
};
