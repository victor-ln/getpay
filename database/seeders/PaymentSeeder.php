<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Payment;
use App\Models\User;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pega a primeira conta e o primeiro usuário para usar nos testes
        $account = Account::first();
        $user = User::first();

        // Se não houver conta ou usuário, não faz nada.
        if (!$account || !$user) {
            $this->command->info('Nenhuma conta ou usuário encontrado, pulando o PaymentSeeder.');
            return;
        }

        $this->command->info("Criando pagamentos de teste para a conta: {$account->name}...");

        // Cria 5 transações de ENTRADA (IN)
        for ($i = 1; $i <= 5; $i++) {
            $amount = rand(50, 500);
            $fee = $amount * 0.05; // 5% de taxa
            $cost = 2.50; // Custo fixo do gateway

            Payment::create([
                'account_id' => $account->id,
                'user_id' => $user->id,
                'name' => "Cliente Final " . chr(64 + $i), // Cliente Final A, B, C...
                'amount' => $amount,
                'fee' => $fee,
                'cost' => $cost,
                'platform_profit' => $fee - $cost,
                'type_transaction' => 'IN',
                'status' => 'paid',
                'created_at' => now()->subDays($i),
            ]);
        }

        // Cria 2 transações de SAÍDA (OUT)
        for ($i = 1; $i <= 2; $i++) {
            $amount = rand(100, 200);
            $fee = 5.00; // Taxa de saque
            $cost = 1.50; // Custo do gateway para saque

            Payment::create([
                'account_id' => $account->id,
                'user_id' => $user->id,
                'name' => "Pagamento Fornecedor " . $i,
                'amount' => $amount,
                'fee' => $fee,
                'cost' => $cost,
                'platform_profit' => $fee - $cost,
                'type_transaction' => 'OUT',
                'status' => 'paid',
                'created_at' => now()->subDays($i * 2),
            ]);
        }

        $this->command->info("Pagamentos de teste criados com sucesso!");
    }
}
