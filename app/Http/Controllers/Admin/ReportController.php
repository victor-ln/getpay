<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary()
    {
        
        $currentStartDate = Carbon::parse('2025-09-01 17:10:00');
        $currentEndDate = now();

        
        $durationInSeconds = $currentEndDate->diffInSeconds($currentStartDate);
        $previousStartDate = $currentStartDate->clone()->subSeconds($durationInSeconds);
        $previousEndDate = $currentStartDate->clone();

        
        $fetchData = function ($startDate, $endDate) {
            return DB::table('payments')
                ->join('accounts', 'payments.account_id', '=', 'accounts.id') 
                ->whereBetween('payments.created_at', [$startDate, $endDate])
                ->where('payments.status', 'paid')
                ->select(
                    'payments.account_id',
                    'accounts.name as account_name', 
                    DB::raw("SUM(CASE WHEN payments.type_transaction = 'IN' THEN payments.amount ELSE 0 END) as total_in"),
                    DB::raw("SUM(CASE WHEN payments.type_transaction = 'OUT' THEN payments.amount ELSE 0 END) as total_out"),
                    DB::raw("SUM(COALESCE(payments.fee, 0) - COALESCE(payments.cost, 0)) as total_profit")
                )
                ->groupBy('payments.account_id', 'accounts.name')
                ->get()
                ->keyBy('account_id'); 
        };

        
        $currentData = $fetchData($currentStartDate, $currentEndDate);
        $previousData = $fetchData($previousStartDate, $previousEndDate);

        
        $report = $currentData->map(function ($currentAccountStats) use ($previousData) {
            $previousAccountStats = $previousData->get($currentAccountStats->account_id);

            $currentAccountStats->profit_percentage_change = $this->calculatePercentageChange(
                $currentAccountStats->total_profit,
                $previousAccountStats?->total_profit
            );

            return $currentAccountStats;
        });


        return view('admin.reports.summary', [
            'report' => $report,
            'currentStartDate' => $currentStartDate,
            'currentEndDate' => $currentEndDate,
        ]);
    }

    /**
     * Helper function to calculate percentage change safely.
     */
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0 || $previous == null) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }
}