<?php


namespace App\Jobs;

use App\Models\Payment;
use App\Models\DailyBalance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateDailyBalances implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $payments = Payment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN type = 'in' THEN amount ELSE 0 END) as total_in"),
                DB::raw("SUM(CASE WHEN type = 'out' THEN amount ELSE 0 END) as total_out")
            )
                ->where('user_id', $user->id)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            foreach ($payments as $p) {
                DailyBalance::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $p->date],
                    ['total_in' => $p->total_in, 'total_out' => $p->total_out]
                );
            }
        }
    }
}
