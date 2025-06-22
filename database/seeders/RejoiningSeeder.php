<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rejoining;
use Carbon\Carbon;

class RejoiningSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        Rejoining::insert([
            [
                'user_id' => 3,
                'title' => 'Rejoined after resignation',
                'description' => 'Employee rejoined after 2 months break',
                'resignated_date' => $now->copy()->subMonths(2)->format('Y-m-d'),
                'rejoined_date' => $now->format('Y-m-d'),
                 'company_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'user_id' => 4,
                'title' => 'Rejoined with promotion',
                'description' => 'Employee rejoined as Team Lead',
                'resignated_date' => $now->copy()->subMonths(4)->format('Y-m-d'),
                'rejoined_date' => $now->copy()->subWeeks(1)->format('Y-m-d'),
                 'company_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
