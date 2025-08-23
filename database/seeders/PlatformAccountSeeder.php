<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bank;
use App\Models\PlatformAccount;

class PlatformAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $banks = Bank::all();
        foreach ($banks as $bank) {
            PlatformAccount::firstOrCreate(
                ['bank_id' => $bank->id],
                ['account_name' => $bank->name . ' Account', 'current_balance' => 0]
            );
        }
    }
}
