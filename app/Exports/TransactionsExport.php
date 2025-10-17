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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldQueue, WithEvents, WithColumnFormatting
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
        // ✅ [CORREÇÃO] A lógica de filtros agora espelha a do seu DashboardController
        $query = Payment::query()->where('account_id', $this->accountId);

        $request = new \Illuminate\Http\Request($this->filters);

        // Lógica de filtro de data
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            $period = $request->input('date_filter', 'today');
            switch ($period) {
                case 'yesterday':
                    $query->whereDate('created_at', now()->subDay());
                    break;
                case '7':
                    $query->where('created_at', '>=', now()->subDays(7)->startOfDay());
                    break;
                case '30':
                    $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
                    break;
                case 'today':
                    $query->whereDate('created_at', now());
                    break;
            }
        }

        // Outros filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type_transaction')) {
            $query->where('type_transaction', $request->type_transaction);
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('document', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('external_payment_id', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query->latest();
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

    /**
     * ✅ [CORREÇÃO] Define o formato das colunas.
     */
    public function columnFormats(): array
    {
        // Força a coluna A (onde está o 'External ID') a ser tratada como TEXTO no Excel.
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_NUMBER_00, // Formata Amount com 2 casas decimais
            'D' => NumberFormat::FORMAT_NUMBER_00, // Formata Fee com 2 casas decimais
        ];
    }
}
