<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledTake;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class DispatchScheduledTakes extends Command
{
    protected $signature = 'takes:dispatch-scheduled';
    protected $description = 'Verifica os "Takes" agendados e despacha os que estão na hora de rodar.';

    public function handle()
    {
        $this->info('Verificando "Takes" agendados...');

        // Busca apenas os agendamentos que estão ativos
        $scheduledTakes = ScheduledTake::where('is_active', true)->get();

        if ($scheduledTakes->isEmpty()) {
            $this->info('Nenhum "Take" agendado ativo encontrado.');
            return 0;
        }

        foreach ($scheduledTakes as $task) {
            // Usa o CronExpression para verificar se está na hora de rodar
            $cron = new \Cron\CronExpression($task->getCronExpression());

            if ($cron->isDue()) {
                $this->info("Disparando Take para o banco ID: {$task->bank_id} (Frequência: {$task->frequency})");

                // Dispara o nosso robô especialista em segundo plano
                Artisan::call('takes:execute-for-bank', [
                    'bankId' => $task->bank_id,
                    '--no-interaction' => true,
                ]);
            }
        }

        $this->info('Verificação concluída.');
        return 0;
    }
}
