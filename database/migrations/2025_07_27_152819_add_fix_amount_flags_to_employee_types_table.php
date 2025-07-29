<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFixAmountFlagsToEmployeeTypesTable extends Migration
{
    public function up()
    {
        Schema::table('employee_types', function (Blueprint $table) {
            $table->boolean('pf_fix_amount')->default(false);
            $table->boolean('esi_fix_amount')->default(false);
            $table->boolean('tds_fix_amount')->default(false);
            $table->boolean('prof_tax_fix_amount')->default(false);
        });
    }

    public function down()
    {
        Schema::table('employee_types', function (Blueprint $table) {
            $table->dropColumn([
                'pf_fix_amount',
                'esi_fix_amount',
                'tds_fix_amount',
                'prof_tax_fix_amount',
            ]);
        });
    }
}
