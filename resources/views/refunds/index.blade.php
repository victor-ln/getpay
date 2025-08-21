@extends('layouts/contentNavbarLayout')

@section('title', 'Refund Management - Acquirers')


@section('page-style')
@vite('resources/assets/css/refunds-datatable.css')
@endsection


@section('page-script')
@vite(['resources/assets/js/ui-toasts.js'])
@vite(['resources/assets/js/transaction-receipt.js'])
@vite(['resources/assets/js/refunds.js'])
@vite(['resources/assets/js/refunds-datatable.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Management /</span> Refunds
</h4>

{{-- Seção de KPIs de Reembolso --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Total Refunded</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">{{ number_format($kpis['total_refunded'] ?? 0, 2, ',', '.') }}</h4>
                            <small class="text-muted">BRL</small>
                        </div>
                        <small>In the selected period</small>
                    </div>
                    <span class="badge bg-label-danger rounded p-2"><i class="bx bx-dollar-circle bx-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Refund Transactions</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">{{ $kpis['refund_count'] ?? 0 }}</h4>
                        </div>
                        <small>In the selected period</small>
                    </div>
                    <span class="badge bg-label-warning rounded p-2"><i class="bx bx-receipt bx-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Refund Rate</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">{{ number_format($kpis['refund_rate'] ?? 0, 2) }}%</h4>
                        </div>
                        <small>Of total deposit value</small>
                    </div>
                    <span class="badge bg-label-info rounded p-2"><i class="bx bx-line-chart-down bx-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span>Refunds Pending Action</span>
                        <div class="d-flex align-items-end mt-2">
                            <h4 class="mb-0 me-2">{{ $kpis['pending_refunds'] ?? 0 }}</h4>
                        </div>
                        <small>Requires manual review</small>
                    </div>
                    <span class="badge bg-label-danger rounded p-2"><i class="bx bx-error-circle bx-sm"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- Card principal com Filtros e Tabela --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Transaction History</h5>
    </div>

    {{-- BARRA DE FILTROS CUSTOMIZADA --}}
    <div class="card-body border-top">
        <form method="GET" action="{{ route('refunds.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" name="status" id="status-filter">
                        <option value="refundable" @selected(request('status', 'refundable' )=='refundable' )>Refundable (Paid)</option>
                        <option value="refunded" @selected(request('status')=='refunded' )>Refunded</option>
                        <option value="all" @selected(request('status')=='all' )>All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search User</label>
                    <input type="text" class="form-control" name="search" placeholder="Name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From</label>
                    <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To</label>
                    <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
    </div>

    {{-- CONTAINER DA DATATABLE --}}
    <div class="card-datatable table-responsive pt-0">
        <table class="table table-hover" id="refunds-datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                <tr class="{{ $payment->status == 'refunded' ? 'table-light' : '' }}">
                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div class="{{ $payment->status == 'refunded' ? 'text-muted' : '' }}">{{ $payment->user->name ?? 'N/A' }}</div>
                        <small class="text-muted">{{ $payment->user->email ?? '' }}</small>
                    </td>
                    <td class="payment-amount" data-amount="{{ $payment->amount }}">
                        <strong class="{{ $payment->status == 'refunded' ? 'text-decoration-line-through text-muted' : 'text-success' }}">
                            + R$ {{ number_format($payment->amount, 2, ',', '.') }}
                        </strong>
                    </td>
                    <td class="{{ $payment->status == 'refunded' ? 'text-decoration-line-through text-muted' : '' }}">
                        R$ {{ number_format($payment->fee, 2, ',', '.') }}
                    </td>
                    <td>
                        @if($payment->status == 'paid')<span class="badge bg-label-success">Paid</span>
                        @elseif($payment->status == 'refunded')<span class="badge bg-label-secondary">Refunded</span>
                        @else<span class="badge bg-label-info">{{ Str::ucfirst($payment->status) }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center align-items-center">
                            @if($payment->status == 'paid')
                            <button class="btn btn-sm btn-outline-danger btn-refund" data-id="{{ $payment->id }}">
                                <i class="bx bx-undo me-1"></i> Refund
                            </button>
                            @else
                            <button class="btn btn-sm btn-secondary" disabled>
                                <i class="bx bx-check-double me-1"></i> Refunded
                            </button>
                            @endif
                            <button class="btn btn-sm btn-icon btn-outline-secondary view-receipt-btn ms-2" data-payment-id="{{ $payment->id }}" title="View Receipt">
                                <i class="bx bx-receipt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                {{-- Esta linha só será mostrada se não houver dados. A DataTable mostrará sua própria mensagem. --}}
                <tr>
                    <td colspan="6" class="text-center">No transactions found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>


    </div>
</div>

{{-- Inclui o modal de confirmação de refund e o offcanvas de comprovante --}}
@include('refunds._refund-modal')
<x-transaction-receipt />

@endsection