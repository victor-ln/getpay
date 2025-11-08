<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // $schedule->job(new \App\Jobs\UpdateDailyBalances)->dailyAt('01:00');
        // $schedule->job(new \App\Jobs\UpdateWeeklySummaries)->weeklyOn(1, '02:00'); // Segunda
        // $schedule->job(new \App\Jobs\UpdateMonthlySummaries)->monthlyOn(1, '03:00'); // Dia 1 do mês

        //Executa a retirada de lucro para o Banco ID 3 a cada 10 minutos.
        // $schedule->command('takes:execute-for-bank 3')->everyTenMinutes();

        // $schedule->command('schedule:dispatch-takes')->everyMinute();
        // $schedule->command('takes:dispatch-scheduled')->everyMinute();
        $schedule->command('payments:verify-pending 30')->everyThreeHours()->withoutOverlapping();
        $schedule->command('payments:summarize-hourly')->hourlyAt(5);   
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
