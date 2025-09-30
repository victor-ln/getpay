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
use App\Services\PayoutTakeService;
use Illuminate\Support\Facades\Log;

class ProcessTakePayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $takeId;
    protected $sourceBankId;
    protected $destinationId;
    protected $amount;

    public function __construct(int $takeId, int $sourceBankId, int $destinationId, float $amount)
    {
        $this->takeId = $takeId;
        $this->sourceBankId = $sourceBankId;
        $this->destinationId = $destinationId;
        $this->amount = $amount;
    }

    public function handle(PayoutTakeService $payoutTakeService): void
    {

        $take = PlatformTake::find($this->takeId);
        $bank = Bank::find($this->sourceBankId);
        $destination = PayoutDestination::find($this->destinationId);



        if (!$take || !$bank || !$destination) {


            if ($take) {
                $take->update([
                    'payout_status' => 'failed',
                    'payout_failure_reason' => 'Missing required records (bank or destination).'
                ]);
            }
            return;
        }

        try {


            // 2. Chama o serviço
            $result = $payoutTakeService->execute($bank, $destination, $this->amount);



            if ($result['success']) {
                Log::info("✅ Pedido de saque para o Take #{$this->takeId} enviado com sucesso para o banco {$bank->name}.", [
                    'result_data' => $result['data'] ?? null
                ]);
            } else {
                Log::error("❌ PayoutTakeService retornou falha", [
                    'take_id' => $this->takeId,
                    'message' => $result['message'] ?? 'N/A',
                    'error' => $result['error'] ?? 'N/A',
                    'full_result' => $result
                ]);
            }
        } catch (\Throwable $e) {
            // Captura TUDO, incluindo erros fatais
            Log::error("❌ EXCEÇÃO CAPTURADA NO JOB", [
                'take_id' => $this->takeId,
                'exception_class' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $take->update([
                'payout_status' => 'failed',
                'payout_failure_reason' => 'Critical exception: ' . $e->getMessage(),
            ]);

            // Re-lança a exceção para o queue handler registrar
            throw $e;
        }

        Log::info('🟢 JOB FINALIZADO', [
            'take_id' => $this->takeId
        ]);
    }
}
