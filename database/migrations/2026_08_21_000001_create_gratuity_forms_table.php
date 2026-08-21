<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generated gratuity claim forms (LIC group gratuity) – one row per generated PDF.
     */
    public function up(): void
    {
        Schema::create('gratuity_forms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->unsigned()->nullable()->default(null);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('employee_id')->unsigned();
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('filename');
            $table->string('file_path');
            $table->date('date_of_joining')->nullable()->default(null);
            $table->date('exit_date')->nullable()->default(null);
            $table->string('total_service', 50)->nullable()->default(null);
            $table->decimal('salary_on_exit', 12, 2)->default(0);
            $table->decimal('gratuity_amount', 12, 2)->default(0);
            $table->json('form_data')->nullable();               // exact $f array used to render the PDF (for re-download)
            $table->bigInteger('created_by')->unsigned()->nullable()->default(null);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gratuity_forms');
    }
};
