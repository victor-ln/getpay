@extends('layouts/contentNavbarLayout')

@section('title', 'Partner Dashboard')

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Partner Portal /</span> Dashboard
</h4>

{{-- Filtros de Data (para a Fase 2) --}}
<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="date_filter_preset" class="form-label">Date Range</label>
                <select id="date_filter_preset" name="date_filter" class="form-select">
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="7_days" selected>Last 7 Days</option>
                    <option value="30_days">Last 30 Days</option>
                    <option value="all">All Time</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" disabled>
            </div>
            <div class="col-md-2">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" disabled>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- Cards de KPIs Globais --}}
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Total Referred Accounts</h6>
                <h4 class="mb-0">{{ $kpis['total_referred_accounts'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Referred Volume (Period)</h6>
                <h4 class="mb-0">R$ {{ number_format($kpis['volume_this_month'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Commission (Period)</h6>
                <h4 class="mb-0 text-success">R$ {{ number_format($kpis['commission_this_month'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Total Commission (All Time)</h6>
                <h4 class="mb-0 text-success">R$ {{ number_format($kpis['total_commission_all_time'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Tabela de Contas (Visão "Não Misturada") --}}
<div class="card">
    <h5 class="card-header">Managed Accounts Overview</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Status</th>
                    <th>Registered On</th>
                    <th class="text-end">Volume IN (Period)</th>
                    <th class="text-end">Volume OUT (Period)</th>
                    <th class="text-end">Your Commission (Period)</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($accountsData as $account)
                <tr>
                    <td>
                        {{-- No futuro, este pode ser um link para um relatório detalhado da conta --}}
                        <strong>{{ $account->name }}</strong>
                    </td>
                    <td>
                        @if($account->active)
                        <span class="badge bg-label-success">Active</span>
                        @else
                        <span class="badge bg-label-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $account->created_at->format('Y-m-d') }}</td>
                    {{-- Por agora, os KPIs por conta estão com placeholders (0) --}}
                    {{-- Na Fase 2, vamos calcular estes valores --}}
                    <td class="text-end">R$ 0,00</td>
                    <td class="text-end">R$ 0,00</td>
                    <td class="text-end"><strong class="text-success">R$ 0,00</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center p-5">
                        <h5 class="mb-0">You are not managing any accounts yet.</h5>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection