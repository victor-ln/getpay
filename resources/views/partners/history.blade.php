@extends('layouts/contentNavbarLayout')

@section('title', 'Account History')

@section('content')
{{-- Cabeçalho da página --}}
<div class="d-flex justify-content-between align-items-center">
    <h6 class="py-3 mb-4">
        <span class="text-muted fw-light">Contas /</span> History Account
    </h6>
    <a href="{{ route('partner.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to My Accounts
    </a>
</div>

{{-- Nome da Conta em destaque --}}
<h3 class="mb-4">Details of: <span class="text-primary">{{ $account->name }}</span></h3>

<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('accounts.history', $account) }}" method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">De</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Até</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Cards com os KPIs (Indicadores) --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Total Volume (Gross)</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">R$ {{ number_format($stats->total_volume, 2, ',', '.') }}</h4>
                        </div>
                        <small>Sum of all transactions</small>
                    </div>
                    <span class="badge bg-label-primary rounded p-2"><i class="bx bx-trending-up bx-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Platform Fees</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">R$ {{ number_format($stats->platform_fees, 2, ',', '.') }}</h4>
                        </div>
                        <small>Total fee charged</small>
                    </div>
                    <span class="badge bg-label-success rounded p-2"><i class="bx bx-dollar bx-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Operating costs</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">R$ {{ number_format($stats->gateway_costs, 2, ',', '.') }}</h4>
                        </div>
                        <small>Total cost paid</small>
                    </div>
                    <span class="badge bg-label-danger rounded p-2"><i class="bx bx-wallet bx-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Net Account Profit</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">R$ {{ number_format($stats->net_profit, 2, ',', '.') }}</h4>
                        </div>
                        <small>Profit to Division (Fee - Cost)</small>
                    </div>
                    <span class="badge bg-label-info rounded p-2"><i class="bx bx-pie-chart-alt bx-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabela de Histórico de Pagamentos --}}
<div class="card">
    <h5 class="card-header">Transaction History</h5>
    {{-- TODO: Adicionar filtros de período aqui no futuro --}}
    <div class="table-responsive text-nowrap">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Fee</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th class="text-end">Amount (gross)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                <tr>
                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @if ($payment->type_transaction == 'IN')
                        <span class="badge bg-label-success">IN</span>
                        @else
                        <span class="badge bg-label-warning">OUT</span>
                        @endif
                    </td>
                    <td>{{ $payment->name }}</td>
                    <td>R$ {{ number_format($payment->fee, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($payment->cost, 2, ',', '.') }}</td>
                    <td><span class="badge bg-label-primary">{{ ucfirst($payment->status) }}</span></td>
                    <td class="text-end">R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No transactions found for this account.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection