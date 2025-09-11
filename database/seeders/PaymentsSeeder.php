<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PaymentsSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('pt_BR');

        $payments = [];

        for ($i = 0; $i < 500; $i++) {
            $amount = $faker->randomFloat(2, 10, 5000);
            $fee = round($amount * 0.035, 2);
            $cost = round($amount * 0.02, 2);
            $platformProfit = round($fee - $cost, 2);

            $payments[] = [
                'user_id' => 1,
                'provider_id' => 1,
                'account_id' => 1,
                'provider_transaction_id' => 'TXN_' . $faker->uuid,
                'provider' => $faker->randomElement(['stripe', 'pagseguro', 'mercadopago', 'paypal', 'pix']),
                'external_payment_id' => 'EXT_' . $faker->randomNumber(8),
                'amount' => $amount,
                'fee' => $fee,
                'cost' => $cost,
                'platform_profit' => $platformProfit,
                'type_transaction' => $faker->randomElement(['IN', 'OUT']),
                'status' => $faker->randomElement(['pending', 'paid', 'failed', 'cancelled', 'processing']),
                'end_to_end_id' => 'E' . $faker->numerify('##########'),
                'provider_response_data' => json_encode([
                    'response_code' => $faker->randomNumber(3),
                    'message' => $faker->sentence,
                    'timestamp' => now()->toISOString()
                ]),
                'name' => $faker->name,
                'document' => $faker->cpf(false),
                'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => now(),
                'deleted_at' => null,
            ];
        }

        // Inserir em lotes de 100
        foreach (array_chunk($payments, 100) as $chunk) {
            DB::table('payments')->insert($chunk);
        }

        $this->command->info('500 registros de pagamentos criados com sucesso!');
    }
}
