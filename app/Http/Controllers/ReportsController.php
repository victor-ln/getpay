<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{

    public function index()
    {




        if (Auth::user()->level == 'admin') {
            $transactions = Payment::limit(6)->orderBy('created_at', 'desc')->get();
        } else {
            $transactions = Payment::where('user_id', Auth::user()->id)->limit(6)->get();
        }

        // Obter o ano atual e anterior
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        // Buscar dados de pagamentos do ano atual e anterior
        $currentYearData = $this->getMonthlyPaymentData($currentYear);
        $previousYearData = $this->getMonthlyPaymentData($previousYear);



        // Obter dados para o gráfico de crescimento
        $growthData = $this->getGrowthData();


        // Obter dados para o gráfico de relatório de perfil
        $profileReportData = $this->getProfileReportData();

        // Obter estatísticas de pedidos
        $orderStatistics = $this->getOrderStatistics();



        // Obter dados para o gráfico de receita
        $incomeChartData = $this->getIncomeChartData();



        // Obter dados para o gráfico de despesas semanais
        $weeklyExpensesData = $this->getWeeklyExpensesData();
        $comparisonString = $this->getWeeklyExpensesComparison();



        $confirmedMounthlyPayments = $this->getMonthTotalPayIn();
        $confirmedPayOutMounthlyPayments = $this->getMonthTotalPayOut();


        // Passar os dados para a view
        return view('reports.index', [
            'currentYear' => $currentYear,
            'previousYear' => $previousYear,
            'transactions' => $transactions,
            'revenueData' => [
                'currentYear' => $currentYearData,
                'previousYear' => $previousYearData
            ],
            'growthData' => $growthData,
            'profileReportData' => $profileReportData,
            'orderStatistics' => $orderStatistics,
            'incomeChartData' => $incomeChartData,
            'weeklyExpensesData' => $weeklyExpensesData,
            'confirmedMounthlyPayments' => $confirmedMounthlyPayments,
            'confirmedPayOutMounthlyPayments' => $confirmedPayOutMounthlyPayments,
            'comparisonString' => $comparisonString
        ]);
    }

    /**
     * Obtém os dados de pagamentos mensais para um ano específico
     * 
     * @param int $year
     * @return array
     */
    private function getMonthlyPaymentData($year)
    {
        $monthlyData = array_fill(0, 12, 0);

        if (Auth::user()->level == 'admin') {
            $payments = Payment::where('status', 'paid')
                ->where('type_transaction', 'IN')
                ->whereYear('created_at', $year)
                ->selectRaw("EXTRACT(MONTH FROM created_at) as month, SUM(amount) as total")
                ->groupByRaw("EXTRACT(MONTH FROM created_at)")
                ->get();
        } else {
            $payments = Payment::where('status', 'paid')
                ->where('type_transaction', 'IN')
                ->where('user_id', Auth::user()->id)
                ->whereYear('created_at', $year)
                ->selectRaw("EXTRACT(MONTH FROM created_at) as month, SUM(amount) as total")
                ->groupByRaw("EXTRACT(MONTH FROM created_at)")
                ->get();
        }

        foreach ($payments as $payment) {
            $monthIndex = (int)$payment->month - 1;
            $monthlyData[$monthIndex] = (float) $payment->total;
        }

        return $monthlyData;
    }



    /**
     * Obtém os dados para o gráfico de crescimento
     * 
     * @return array
     */
    private function getGrowthData()
    {
        // Calcular o crescimento comparando o total de transações do mês atual com o mês anterior
        $currentMonth = Carbon::now()->month;
        $previousMonth = Carbon::now()->subMonth()->month;
        $currentYear = Carbon::now()->year;


        $currentMonthTotal = $this->getMonthTotal($currentMonth, $currentYear);
        $previousMonthTotal = $this->getMonthTotal($previousMonth, $currentYear);

        $growthPercentage = 0;
        if ($previousMonthTotal > 0) {
            $growthPercentage = round(($currentMonthTotal - $previousMonthTotal) / $previousMonthTotal * 100);
        }

        // Garantir que o percentual esteja entre 0 e 100 para o gráfico radial
        $growthPercentage = max(0, min(100, $growthPercentage));

        return [
            'percentage' => $growthPercentage,
        ];
    }

    /**
     * Obtém o total de transações para um mês específico
     * 
     * @param int $month
     * @param int $year
     * @return float
     */
    private function getMonthTotal($month, $year)
    {
        $query = Payment::where('status', 'paid')
            ->where('type_transaction', 'IN')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if (Auth::user()->level != 'admin') {
            $query->where('user_id', Auth::user()->id);
        }

        return $query->sum('amount');
    }

    private function getMonthTotalPayIn($month = null, $year = null)
    {
        $currentMonth = Carbon::now()->month;
        $previousMonth = Carbon::now()->subMonth()->month;
        $currentYear = Carbon::now()->year;

        $month = $month ?? $currentMonth;
        $year = $year ?? $currentYear;

        $query = Payment::where('status', 'paid')
            ->where('type_transaction', 'IN')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if (Auth::user()->level != 'admin') {
            $query->where('user_id', Auth::user()->id);
        }

        return $query->sum('amount');
    }

    private function getMonthTotalPayOut($month = null, $year = null)
    {
        $currentMonth = Carbon::now()->month;
        $previousMonth = Carbon::now()->subMonth()->month;
        $currentYear = Carbon::now()->year;

        $month = $month ?? $currentMonth;
        $year = $year ?? $currentYear;

        $query = Payment::where('status', 'paid')
            ->where('type_transaction', 'OUT')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if (Auth::user()->level != 'admin') {
            $query->where('user_id', Auth::user()->id);
        }

        return $query->sum('amount');
    }

    /**
     * Obtém os dados para o gráfico de relatório de perfil
     * 
     * @return array
     */
    private function getProfileReportData()
    {
        // Obter os últimos 6 meses de dados para o gráfico de linha
        $data = [];
        $currentDate = Carbon::now();

        for ($i = 1; $i >= 0; $i--) {
            $month = $currentDate->copy()->subMonths($i)->month;
            $year = $currentDate->copy()->subMonths($i)->year;

            $data[] = $this->getMonthTotal($month, $year);
        }

        return [
            'data' => $data
        ];
    }

    /**
     * Obtém as estatísticas de pedidos para o gráfico de rosca
     * 
     * @return array
     */
    private function getOrderStatistics()
    {
        $query = Payment::where('type_transaction', 'IN');

        if (Auth::user()->level != 'admin') {
            $query->where('user_id', Auth::user()->id);
        }

        $confirmed = $query->clone()->where('status', 'paid')->count();
        $pending = $query->clone()->where('status', 'pending')->count();
        $canceled = $query->clone()->where('status', 'canceled')->count();
        $refused = $query->clone()->where('status', 'refused')->count();

        $totalAmount = $query->clone()->sum('amount');

        $confimedAmount = $query->clone()->where('status', 'paid')->sum('amount');
        $pendingAmount = $query->clone()->where('status', 'pending')->sum('amount');
        $canceledAmount = $query->clone()->where('status', 'canceled')->sum('amount');
        $refusedAmount = $query->clone()->where('status', 'refused')->sum('amount');

        $total = $confirmed + $pending + $canceled + $refused;
        $totalPercentage = $total > 0 ? round(($confirmed / $total) * 100) : 0;

        return [
            'labels' => ['Confirmed', 'Pending', 'Canceled', 'Refused'],
            'series' => [$confirmed, $pending, $canceled, $refused],
            'total' => $totalPercentage,
            'totalValue' => $total,
            'totalAmount' => $totalAmount,
            'confirmedAmount' => $confimedAmount,
            'pendingAmount' => $pendingAmount,
            'canceledAmount' => $canceledAmount,
            'refusedAmount' => $refusedAmount
        ];
    }

    /**
     * Obtém os dados para o gráfico de receita
     * 
     * @return array
     */
    private function getIncomeChartData()
    {
        // Obter os últimos 7 meses de dados para o gráfico de área
        $data = [];
        $categories = [];
        $currentDate = Carbon::now();

        for ($i = 6; $i >= 0; $i--) {
            $month = $currentDate->copy()->subMonths($i)->month;
            $year = $currentDate->copy()->subMonths($i)->year;
            $monthName = $currentDate->copy()->subMonths($i)->format('M');

            $data[] = $this->getMonthTotal($month, $year);
            $categories[] = $monthName;
        }

        return [
            'data' => $data,
            'categories' => $categories
        ];
    }

    /**
     * Obtém os dados para o gráfico de despesas semanais
     * 
     * @return array
     */
    private function getWeeklyExpensesData()
    {
        // Calcular o total de despesas da semana atual
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $query = Payment::where('type_transaction', 'OUT')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]);

        if (Auth::user()->level != 'admin') {
            $query->where('user_id', Auth::user()->id);
        }

        $weeklyExpenses = $query->sum('amount');

        // Calcular o percentual em relação ao limite semanal (exemplo: 1000)
        $weeklyLimit = 1000;
        $percentage = min(100, round(($weeklyExpenses / $weeklyLimit) * 100));

        return [
            'percentage' => $percentage,
            'currency' => '%'
        ];
    }

    /**
     * Calcula as despesas da semana atual e compara com as da semana anterior,
     * retornando uma string descritiva.
     *
     * @return string Ex: "$39k less than last week", "$50k more than last week", "Similar to last week".
     */
    private function getWeeklyExpensesComparison(): string
    {
        // 1. Calcular Despesas da Semana Atual
        $startOfWeekCurrent = Carbon::now()->startOfWeek();
        $endOfWeekCurrent = Carbon::now()->endOfWeek();

        $queryCurrent = Payment::where('type_transaction', 'OUT')
            ->whereBetween('created_at', [$startOfWeekCurrent, $endOfWeekCurrent]);

        // Aplicar filtro de usuário, se não for admin
        if (Auth::user()->level != 'admin') {
            $queryCurrent->where('user_id', Auth::user()->id);
        }

        $weeklyExpensesCurrent = $queryCurrent->sum('amount');

        // 2. Calcular Despesas da Semana Anterior
        $startOfWeekLast = Carbon::now()->subWeek()->startOfWeek();
        $endOfWeekLast = Carbon::now()->subWeek()->endOfWeek();

        $queryLast = Payment::where('type_transaction', 'OUT')
            ->whereBetween('created_at', [$startOfWeekLast, $endOfWeekLast]);

        // Aplicar o mesmo filtro de usuário
        if (Auth::user()->level != 'admin') {
            $queryLast->where('user_id', Auth::user()->id);
        }

        $weeklyExpensesLast = $queryLast->sum('amount');

        // 3. Comparar e Formatar a Saída
        $difference = $weeklyExpensesCurrent - $weeklyExpensesLast;

        // Definir um limiar de "similaridade" para evitar "less" ou "more" para pequenas diferenças
        $similarityThreshold = 0.05; // 5% de diferença, por exemplo

        if (abs($difference) < ($weeklyExpensesLast * $similarityThreshold)) {
            return 'Similar to last week';
        }

        $formattedDifference = $this->formatCurrency($difference); // Usar função auxiliar para formatação

        if ($difference > 0) {
            return "{$formattedDifference} more than last week";
        } else {
            // abs() para remover o sinal negativo, pois já indicamos "less"
            return abs($formattedDifference) . " less than last week";
        }
    }

    /**
     * Função auxiliar para formatar valores monetários em "k" (milhares).
     * Você pode expandir isso para outras moedas ou formatações.
     * @param float $amount
     * @return string
     */
    protected function formatCurrency(float $amount): string
    {
        // Garante que o valor seja absoluto para formatação, o sinal é tratado na comparação
        $absAmount = abs($amount);

        if ($absAmount >= 1000) {
            return round($absAmount / 1000) . 'k'; // Arredonda para o milhar mais próximo e adiciona 'k'
        }
        return number_format($absAmount, 2, '.', ','); // Formata para 2 casas decimais se menor que 1k
    }
}
