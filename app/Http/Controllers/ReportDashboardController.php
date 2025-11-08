<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Account; // Certifique-se que você tem um Model 'Account'
use Carbon\Carbon;

class ReportDashboardController extends Controller
{
    /**
     * Exibe o dashboard de relatórios com filtros.
     */
    public function index(Request $request)
    {
        // --- 1. Validar e Definir Filtros ---
        
        // Pega os valores da request ou define padrões
        $accountId = $request->input('account_id');
        
        // Se a data não for passada, define um padrão (ex: últimos 30 dias)
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay() 
            : now()->subDays(29)->startOfDay(); // 30 dias atrás

        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date'))->endOfDay() 
            : now()->endOfDay();

            
        // --- 2. A Query de Relatório (RÁPIDA) ---
        
        // Começa a query na tabela de sumarização
        $query = DB::table('account_hourly_summaries')
            ->join('accounts', 'account_hourly_summaries.account_id', '=', 'accounts.id')
            ->whereBetween('summary_hour', [$startDate, $endDate]);

        // Adiciona filtro de conta, se ele foi selecionado
        if ($accountId) {
            $query->where('account_hourly_summaries.account_id', $accountId);
        }

        // Agrega os resultados
        $reports = $query->select(
            'accounts.id as account_id',
            'accounts.name as account_name',
            DB::raw('SUM(volume_in) as total_in'),
            DB::raw('SUM(volume_out) as total_out'),
            DB::raw('SUM(total_fees) as total_fees'),
            DB::raw('SUM(total_costs) as total_costs')
        )
        ->groupBy('accounts.id', 'accounts.name')
        ->orderBy('accounts.name')
        ->paginate(25) // Paginação para não carregar 1000 contas de uma vez
        ->appends($request->query()); // Mantém os filtros na paginação


        // --- 3. Dados de Suporte para a View ---

        // Pega a data da última atualização
        $lastUpdateRaw = DB::table('account_hourly_summaries')->max('summary_hour');
        $lastUpdate = $lastUpdateRaw ? Carbon::parse($lastUpdateRaw)->diffForHumans() : 'No data yet';

        // Pega todas as contas para o <select> do filtro
        $accounts = Account::orderBy('name')->get(['id', 'name']);


        // --- 4. Retorna a View ---
        
        return view('reports.dashboard', [
            'reports' => $reports,
            'accounts' => $accounts,
            'lastUpdate' => $lastUpdate,
            'filters' => [ // Usado para manter os filtros na view
                'account_id' => $accountId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ]);
    }
}