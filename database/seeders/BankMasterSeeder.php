<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BankMaster;

class BankMasterSeeder extends Seeder
{
    public function run(): void
    {
        BankMaster::insert([
            [
                'account_number' => '1234567890',
                'bank_name' => 'State Bank of India',
                'ifsc' => 'SBIN0001234',
                'micr' => '400002345',
                'employee_id' => 1, // Make sure user ID 1 exists
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_number' => '9876543210',
                'bank_name' => 'HDFC Bank',
                'ifsc' => 'HDFC0005678',
                'micr' => '500005678',
                'employee_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
