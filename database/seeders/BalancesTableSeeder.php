<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Balance;

class BalancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pega todos os usuários existentes
        $users = User::all();

        foreach ($users as $user) {
            // Cria um saldo para o usuário APENAS se ele ainda não tiver um.
            Balance::firstOrCreate(
                ['user_id' => $user->id],
                ['available_balance' => 0.00, 'blocked_balance' => 0.00]
            );
        }
    }
}
