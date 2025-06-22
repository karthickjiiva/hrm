<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRejoiningsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rejoinings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');              // FK to users or staff_members table
            $table->unsignedBigInteger('company_id');           // FK to companies table
            $table->date('resignated_date')->nullable();
            $table->date('rejoined_date')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rejoinings');
    }
}
