<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPfPensionSchemeToEmployeeTypesTable extends Migration
{
    public function up()
    {
        Schema::table('employee_types', function (Blueprint $table) {
            $table->boolean('pf_pension_scheme')->default(false);
        });
    }

    public function down()
    {
        Schema::table('employee_types', function (Blueprint $table) {
            $table->dropColumn('pf_pension_scheme');
        });
    }
}
