<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmployeeType;

class EmployeeTypeSeeder extends Seeder
{
public function run(): void
{
    $types = [
        ['Full-Time', 40, 20, 10, 5, 12, 1500, 0.75, 500, 2, 200, 10, 1000],
        ['Part-Time', 30, 15, 8, 4, 10, 1000, 0.5, 300, 1.5, 150, 8, 800],
        ['Intern', 20, 10, 5, 2, 5, 500, 0.25, 100, 1, 100, 5, 500],
        ['Contractor', 35, 18, 7, 3, 0, 0, 0, 0, 0, 0, 10, 1500],
        ['Consultant', 38, 19, 8, 4, 0, 0, 0, 0, 0, 0, 12, 2000],
        ['Freelancer', 25, 10, 7, 0, 0, 0, 0, 0, 0, 0, 10, 1000],
        ['Temporary', 30, 14, 6, 3, 8, 1000, 0.5, 250, 1.5, 120, 7, 700],
        ['Remote Full-Time', 40, 20, 12, 5, 12, 1800, 0.8, 550, 2, 250, 10, 1000],
        ['Remote Part-Time', 32, 16, 9, 4, 10, 1300, 0.6, 350, 1.8, 170, 8, 900],
        ['On-Site Consultant', 36, 18, 8, 2, 0, 0, 0, 0, 0, 0, 15, 2500],
        ['Field Staff', 33, 17, 9, 3, 10, 1200, 0.7, 300, 2, 180, 9, 950],
        ['Shift Worker', 34, 16, 8, 4, 11, 1400, 0.75, 450, 2, 220, 11, 1050],
        ['Maintenance Staff', 28, 13, 6, 3, 9, 1000, 0.5, 200, 1.5, 130, 6, 600],
        ['Support Staff', 30, 15, 7, 3, 10, 1100, 0.6, 280, 1.8, 160, 7, 700],
        ['Junior Developer', 35, 18, 10, 5, 12, 1600, 0.8, 500, 2, 200, 10, 1000],
        ['Senior Developer', 40, 20, 12, 6, 12, 1800, 0.9, 600, 2.2, 250, 12, 1200],
        ['Tech Lead', 42, 21, 14, 5, 12, 2000, 0.95, 700, 2.5, 300, 15, 1500],
        ['Project Manager', 38, 19, 10, 5, 12, 1800, 0.85, 550, 2, 220, 11, 1100],
        ['HR Executive', 34, 17, 9, 4, 10, 1300, 0.65, 350, 1.8, 180, 9, 900],
        ['Marketing Executive', 33, 16, 10, 5, 10, 1250, 0.7, 400, 2, 200, 10, 950],
        ['Sales Associate', 31, 15, 11, 4, 10, 1200, 0.6, 300, 1.7, 170, 8, 850],
        ['Business Analyst', 36, 18, 12, 6, 12, 1500, 0.75, 500, 2, 220, 10, 1000],
        ['Data Analyst', 34, 17, 10, 5, 11, 1450, 0.7, 450, 2, 200, 9, 950],
        ['UI/UX Designer', 32, 16, 9, 4, 10, 1300, 0.65, 350, 1.9, 180, 8, 900],
        ['Content Writer', 30, 15, 8, 3, 9, 1200, 0.6, 300, 1.8, 170, 7, 850],
    ];

    foreach ($types as $type) {
    EmployeeType::create([
        'type' => $type[0],
        'basic_percent' => $type[1],
        'hra_percent' => $type[2],
        'allowance_percent' => $type[3],
        'food_allowance_percent' => $type[4],
        
        'pf_enabled' => $type[5] > 0 && $type[6] > 0,
        'pf_percentage' => $type[5],
        'pf_limit' => $type[6],
        
        'esi_enabled' => $type[7] > 0 && $type[8] > 0,
        'esi_percentage' => $type[7],
        'esi_limit' => $type[8],
        
        'prof_tax_enabled' => $type[9] > 0 && $type[10] > 0,
        'prof_tax_percentage' => $type[9],
        'prof_tax_limit' => $type[10],
        
        'tds_enabled' => $type[11] > 0 && $type[12] > 0,
        'tds_percentage' => $type[11],
        'tds_limit' => $type[12],
        
        'status' => 'active',
        'created_by' => 1,
    ]);
}

}
}
