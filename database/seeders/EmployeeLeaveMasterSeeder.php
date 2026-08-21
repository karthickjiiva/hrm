<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmployeeLeaveMaster;

class EmployeeLeaveMasterSeeder extends Seeder
{
    public function run(): void
    {
        EmployeeLeaveMaster::insert([
            [
                'employee_id' => 1, // Make sure user ID 1 exists
                'sl' => 10,          // Sick Leave
                'cl' => 8,           // Casual Leave
                'el' => 15,          // Earned Leave
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_id' => 2,
                'sl' => 12,
                'cl' => 6,
                'el' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
