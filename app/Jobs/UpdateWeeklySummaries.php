<?php


namespace App\Jobs;

use App\Models\Payment;
use App\Models\User;
use App\Models\WeeklySummary;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateWeeklySummaries implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $payments = Payment::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('WEEK(created_at, 1) as week'),
                DB::raw("SUM(CASE WHEN type = 'in' THEN amount ELSE 0 END) as total_in"),
                DB::raw("SUM(CASE WHEN type = 'out' THEN amount ELSE 0 END) as total_out")
            )
                ->where('user_id', $user->id)
                ->groupBy('year', 'week')
                ->get();

            foreach ($payments as $p) {
                WeeklySummary::updateOrCreate(
                    ['user_id' => $user->id, 'year' => $p->year, 'week' => $p->week],
                    ['total_in' => $p->total_in, 'total_out' => $p->total_out]
                );
            }
        }
    }
}
