<?php

namespace App\Exports;

use App\Models\Payment; // Certifique-se que o caminho para seu model está correto
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $request;

    // O construtor da classe recebe a requisição com todos os filtros
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Define os títulos das colunas na primeira linha do arquivo Excel.
     */
    public function headings(): array
    {
        // Adapte os cabeçalhos conforme sua necessidade
        return [
            'External ID',
            'Type',
            'Amount',
            'Fee',
            'Status',
            'Updated At',
        ];
    }

    /**
     * Mapeia e formata os dados de cada linha da consulta.
     * @var Payment $transaction
     */
    public function map($transaction): array
    {
        // Aqui formatamos cada linha para que ela apareça corretamente no Excel
        return [
            $transaction->external_payment_id,
            $transaction->type_transaction,
            $transaction->amount,
            $transaction->fee,
            ucfirst($transaction->status),
            $transaction->created_at->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Constrói e retorna a query com todos os filtros aplicados,
     * garantindo que o export seja igual ao que é visto na tela.
     */
    public function query()
    {
        // Inicia a query com a relação 'user' para evitar N+1 queries no 'map'
        $query = Payment::query()->with('user');

        // REUTILIZA A MESMA LÓGICA DE FILTROS DA SUA VIEW
        if ($this->request->filled('status')) {
            $query->where('status', $this->request->status);
        }

        if ($this->request->filled('type_transaction')) {
            $query->where('type_transaction', $this->request->type_transaction);
        }

        if ($this->request->filled('amount_min')) {
            $query->where('amount', '>=', $this->request->amount_min);
        }

        if ($this->request->filled('amount_max')) {
            $query->where('amount', '<=', $this->request->amount_max);
        }

        if ($this->request->filled('search')) {
            $search = $this->request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('provider_transaction_id', 'like', "%{$search}%")
                  ->orWhere('external_payment_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%");
            });
        }

        // Lógica de filtro de data combinada (predefinida ou customizada)
        if ($this->request->filled('start_date') && $this->request->filled('end_date')) {
            $startDate = Carbon::parse($this->request->start_date)->startOfDay();
            $endDate = Carbon::parse($this->request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else if ($this->request->filled('date_filter')) {
            $filter = $this->request->date_filter;
            if (is_numeric($filter)) {
                $query->where('created_at', '>=', now()->subDays($filter));
            }
        }

        return $query->latest(); // Ordena pelos mais recentes
    }
}