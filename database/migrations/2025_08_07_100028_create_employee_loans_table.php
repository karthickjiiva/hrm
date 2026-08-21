<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeLoansTable extends Migration
{
    public function up()
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('monthly_amount', 10, 2);
            $table->integer('tenure'); // in months
            $table->string('start_month'); // format YYYY-MM
            $table->string('end_month');   // format YYYY-MM
            $table->enum('status', ['active', 'closed', 'foreclosed'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_loans');
    }
}
