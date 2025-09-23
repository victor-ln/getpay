<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\Account;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldQueue, WithEvents
{
    use Exportable;

    protected $filters;
    protected $accountId;
    protected $report;

    /**
     * O construtor agora recebe o registo do relatório.
     */
    public function __construct(array $filters, int $accountId, Report $report)
    {
        $this->filters = $filters;
        $this->accountId = $accountId;
        $this->report = $report;
    }

    public function registerEvents(): array
    {
        return [
            // Antes de o job começar a processar a folha
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->report->update(['status' => 'processing']);
            },
            // Depois de a folha ter sido processada com sucesso
            AfterSheet::class => function (AfterSheet $event) {
                $this->report->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
            },
        ];
    }

    /**
     * ✅ Este método é chamado automaticamente se o job falhar.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Falha na exportação do relatório #" . $this->report->id . ": " . $exception->getMessage());
        $this->report->update([
            'status' => 'failed',
            'failure_reason' => $exception->getMessage()
        ]);
    }

    /**
     * Constrói e retorna a query com todos os filtros aplicados.
     */
    public function query()
    {
        // ✅ [MELHORIA] A lógica de filtros agora é mais limpa e centralizada
        $query = Payment::query()->where('account_id', $this->accountId);

        // Aplica os filtros recebidos do controller
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (!empty($this->filters['type_transaction'])) {
            $query->where('type_transaction', $this->filters['type_transaction']);
        }
        // ... (etc, para todos os seus outros filtros)

        // Lógica de filtro de data
        if (!empty($this->filters['start_date']) && !empty($this->filters['end_date'])) {
            $startDate = Carbon::parse($this->filters['start_date'])->startOfDay();
            $endDate = Carbon::parse($this->filters['end_date'])->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (!empty($this->filters['date_filter'])) {
            $filter = $this->filters['date_filter'];
            if ($filter === 'today') {
                $query->whereDate('created_at', now());
            } elseif ($filter === 'yesterday') {
                $query->whereDate('created_at', now()->subDay());
            } elseif (is_numeric($filter)) {
                $query->where('created_at', '>=', now()->subDays((int)$filter)->startOfDay());
            }
        }

        return $query->latest(); // Ordena pelos mais recentes
    }

    /**
     * Mapeia e formata os dados de cada linha.
     * @var Payment $transaction
     */
    public function map($transaction): array
    {
        return [
            $transaction->external_payment_id,
            $transaction->type_transaction,
            number_format($transaction->amount, 2, ',', '.'), // Formata como moeda
            number_format($transaction->fee, 2, ',', '.'),    // Formata como moeda
            ucfirst($transaction->status),
            $transaction->created_at->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Define os títulos das colunas.
     */
    public function headings(): array
    {
        return [
            'External ID',
            'Type',
            'Amount (BRL)',
            'Fee (BRL)',
            'Status',
            'Date',
        ];
    }
}
