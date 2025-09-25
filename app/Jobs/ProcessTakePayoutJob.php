<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\PlatformTake;
use App\Models\Bank;
use App\Models\PayoutDestination;
use App\Services\PayoutTakeService; // ✅ [CORREÇÃO] Importa o novo serviço
use Illuminate\Support\Facades\Log;

class ProcessTakePayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $takeId;
    protected $sourceBankId;
    protected $destinationId;
    protected $amount;

    /**
     * Create a new job instance.
     */
    public function __construct(int $takeId, int $sourceBankId, int $destinationId, float $amount)
    {
        $this->takeId = $takeId;
        $this->sourceBankId = $sourceBankId;
        $this->destinationId = $destinationId;
        $this->amount = $amount;
    }

    /**
     * Execute the job.
     * ✅ [CORREÇÃO] O método agora recebe o PayoutTakeService
     */
    public function handle(PayoutTakeService $payoutTakeService): void
    {
        Log::info("Iniciando processamento do saque para o Take #{$this->takeId} do banco #{$this->sourceBankId}.");

        // 1. Busca os registos necessários no banco de dados
        $take = PlatformTake::find($this->takeId);
        $bank = Bank::find($this->sourceBankId);
        $destination = PayoutDestination::find($this->destinationId);

        // Uma verificação de segurança para garantir que tudo foi encontrado
        if (!$take || !$bank || !$destination) {
            Log::error("Não foi possível processar o saque para o Take #{$this->takeId}: um dos registos (take, banco ou destino) não foi encontrado.");
            if ($take) {
                $take->update(['payout_status' => 'failed', 'payout_failure_reason' => 'Missing required records (bank or destination).']);
            }
            return;
        }

        try {
            // 2. ✅ [CORREÇÃO] Chama o novo serviço, que é muito mais direto
            $result = $payoutTakeService->execute($bank, $destination, $this->amount);

            // 3. O 'PayoutTakeService' já lida com a criação do 'payment' e a chamada à API.
            // Aqui, apenas verificamos o resultado final para logar.
            if ($result['success']) {
                // A atualização do status do Take para 'completed' ou 'failed'
                // agora pode ser feita pelo webhook de confirmação do payout,
                // ou mantida aqui se o seu adquirente der uma resposta síncrona.
                // Por agora, vamos assumir que o 'payout_status' do Take fica como 'processing'
                // até o webhook de confirmação chegar.
                Log::info("Pedido de saque para o Take #{$this->takeId} enviado com sucesso para o banco {$bank->name}.");
            } else {
                // O próprio serviço já deve ter logado o erro detalhado.
                Log::error("O PayoutTakeService retornou uma falha para o Take #{$this->takeId}. Mensagem: " . ($result['message'] ?? 'N/A'));
            }
        } catch (\Exception $e) {
            // Se ocorrer uma exceção inesperada durante a chamada ao serviço
            Log::error("Exceção crítica no saque para o Take #{$this->takeId} do banco {$bank->name}: " . $e->getMessage());
            $take->update([
                'payout_status' => 'failed',
                'payout_failure_reason' => 'Critical exception: ' . $e->getMessage(),
            ]);
        }
    }
}
