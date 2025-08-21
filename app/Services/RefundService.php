<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User; // Importa o model User
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RefundService
{
    /**
     * Prepara todos os dados para a página de gerenciamento de reembolsos,
     * agora considerando o usuário autenticado.
     *
     * @param array $filters Filtros recebidos do request.
     * @param User $user O usuário autenticado.
     * @return array
     */
    public function getRefundDashboardData(array $filters, User $user): array
    {
        return [
            'kpis' => $this->getKpiData($filters, $user),
            'payments' => $this->getPaymentsForView($filters, $user),
        ];
    }

    /**
     * Calcula os KPIs, respeitando as permissões do usuário.
     */
    private function getKpiData(array $filters, User $user): array
    {
        // Query base para pagamentos concluídos (paid ou refunded)
        $baseQuery = Payment::whereIn('status', ['paid', 'refunded']);

        // ✅ APLICA A REGRA DE PERMISSÃO
        if ($user->level !== 'admin') {
            $baseQuery->where('user_id', $user->id);
        }

        $this->applyDateFilters($baseQuery, $filters);

        $totalDeposited = (clone $baseQuery)->where('type_transaction', 'IN')->sum('amount');
        $totalRefunded = (clone $baseQuery)->where('status', 'refunded')->where('type_transaction', 'IN')->sum('amount');
        $refundCount = (clone $baseQuery)->where('status', 'refunded')->where('type_transaction', 'IN')->count();
        $refundRate = ($totalDeposited > 0) ? ($totalRefunded / $totalDeposited) * 100 : 0;

        // Para reembolsos pendentes, o admin vê todos, o cliente vê apenas os seus.
        $pendingRefundsQuery = Payment::where('status', 'refunding');
        if ($user->level !== 'admin') {
            $pendingRefundsQuery->where('user_id', $user->id);
        }
        $pendingRefunds = $pendingRefundsQuery->count();

        return [
            'total_refunded' => $totalRefunded,
            'refund_count' => $refundCount,
            'refund_rate' => $refundRate,
            'pending_refunds' => $pendingRefunds,
        ];
    }

    /**
     * Busca uma lista paginada de transações com base nos filtros e permissões.
     */
    private function getPaymentsForView(array $filters, User $user)
    {
        // Começa buscando transações do tipo IN
        $query = Payment::with('user')->where('type_transaction', 'IN');

        // ✅ APLICA A REGRA DE PERMISSÃO
        if ($user->level !== 'admin') {
            $query->where('user_id', $user->id);
        }

        // ✅ APLICA O NOVO FILTRO DE STATUS
        $statusFilter = $filters['status'] ?? 'refundable'; // 'refundable' é o padrão
        if ($statusFilter === 'refundable') {
            $query->where('status', 'paid');
        } elseif ($statusFilter === 'refunded') {
            $query->where('status', 'refunded');
        } else {
            // 'all' ou qualquer outro valor mostra ambos
            $query->whereIn('status', ['paid', 'refunded']);
        }

        if (empty($filters['date_from']) && empty($filters['date_to'])) {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        } else {
            // Se houver filtro, aplica-o
            $this->applyDateFilters($query, $filters);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->get();
    }

    /**
     * Helper para aplicar filtros de data.
     */
    private function applyDateFilters($query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }
}
