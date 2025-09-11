@extends('layouts/contentNavbarLayout')

@section('title', 'History (Takes)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-3 mb-0">
        <span class="text-muted fw-light">Admin /</span> History(Takes)
    </h4>
    <a href="{{ route('admin.takes.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Get new Take
    </a>
</div>

{{-- [O TOQUE DE UX] Cards com KPIs do Mês Atual --}}
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Profit Withdrawn (This Month)</h6>
                <h4 class="mb-0">R$ {{ number_format($kpis['profit_this_month'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Number of "Takes" (This Month)</h6>
                <h4 class="mb-0">{{ $kpis['takes_this_month'] }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Tabela com o Histórico de Takes --}}
<div class="card">
    <h5 class="card-header">History</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Period Processed</th>
                    <th class="text-end">Net profit</th>
                    <th class="text-center">Withdrawal Status</th>
                    <th>Executed in</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($takes as $take)
                <tr>
                    <td><strong>#{{ $take->id }}</strong></td>
                    <td>
                        <small>
                            De: {{ $take->start_date->format('d/m/y H:i') }}<br>
                            Até: {{ $take->end_date->format('d/m/y H:i') }}
                        </small>
                    </td>
                    <td class="text-end">
                        <strong class="text-success">R$ {{ number_format($take->total_net_profit, 2, ',', '.') }}</strong>
                    </td>
                    <td class="text-center">
                        @if($take->payout_status == 'paid')
                        <span class="badge bg-label-success">Completed</span>
                        @elseif($take->payout_status == 'processing')
                        <span class="badge bg-label-warning">Processing</span>
                        @elseif($take->payout_status == 'failed')
                        <span class="badge bg-label-danger">Failed</span>
                        @else
                        <span class="badge bg-label-secondary">Pending</span>
                        @endif
                    </td>
                    <td>{{ $take->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('admin.takes.show', $take) }}" class="btn btn-sm btn-outline-primary">
                            View Report
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center p-5">
                        No "Take" has been generated yet. <a href="{{ route('admin.takes.create') }}">Click here to generate the first one.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-center">
        {{ $takes->links() }}
    </div>
</div>
@endsection