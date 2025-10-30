<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment; // Importe seu Model de Pagamento
use Illuminate\Support\Facades\Log;

class ReportPendingOwnership extends Command
{
    protected $signature = 'report:pending-ownership 
                            {--file=storage/app/reconcile_ids.txt : O ficheiro de entrada com os IDs.}
                            {--output=storage/app/identification_report.csv : O ficheiro de saída do relatório.}';

    protected $description = 'Identifica a qual account_id e qual amount pertencem os IDs pendentes';

    public function handle()
    {
        $this->info("Iniciando relatório de identificação...");

        // --- 1. Ler a lista de IDs do ficheiro de entrada ---
        $filePath = $this->option('file');
        if (!file_exists(base_path($filePath))) {
            $this->error("Ficheiro de IDs de entrada não encontrado em: " . base_path($filePath));
            return 1;
        }

        $paidExternalIds = array_filter(array_map('trim', file(base_path($filePath), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)));

        if (empty($paidExternalIds)) {
            $this->error("Ficheiro de IDs está vazio.");
            return 1;
        }

        $this->info(count($paidExternalIds) . " IDs pagos encontrados no ficheiro de entrada.");

        // --- 2. Preparar o ficheiro de Saída (Relatório CSV) ---
        $outputPath = base_path($this->option('output'));
        try {
            $outputHandle = fopen($outputPath, 'w');
            // Escreve o cabeçalho do CSV
            fputcsv($outputHandle, ['local_payment_id', 'provider_transaction_id', 'account_id', 'amount', 'current_status']);
        } catch (\Exception $e) {
            $this->error("Não foi possível criar o ficheiro de relatório em: $outputPath");
            Log::error("Erro ao criar relatório CSV: " . $e->getMessage());
            return 1;
        }

        // --- 3. Processar Pagamentos em Lotes (Eficiente) ---
        $foundCount = 0;

        // Buscamos APENAS pagamentos que estão 'pending' E que estão na sua lista.
        Payment::where('status', 'pending')
            ->whereIn('provider_transaction_id', $paidExternalIds) // 'external_id' é o seu provider_transaction_id
            ->select('id', 'external_payment_id', 'account_id', 'amount', 'status') // Seleciona apenas o que precisamos
            ->chunkById(500, function ($payments) use ($outputHandle, &$foundCount) {

                foreach ($payments as $payment) {

                    // Escreve a linha no nosso novo relatório CSV
                    fputcsv($outputHandle, [
                        $payment->id,
                        $payment->external_payment_id,
                        $payment->account_id,
                        $payment->amount,
                        $payment->status
                    ]);

                    $foundCount++;
                }

                $this->info("Processado lote... $foundCount IDs encontrados e mapeados.");
            });

        fclose($outputHandle);

        $this->info("--- Relatório Finalizado ---");
        $this->info("Total de Transações Identificadas: $foundCount");
        $this->info("O relatório foi salvo em: " . $this->option('output'));

        return 0;
    }
}
