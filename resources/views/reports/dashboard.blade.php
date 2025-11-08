@extends('layouts/contentNavbarLayout')

{{-- O título da página --}}
@section('title', 'Reports Dashboard')



@section('page-script')

@endsection


@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    
    <div class="card mb-4">
        <h5 class="card-header">Report Filters</h5>
        <div class="card-body">
            <form action="{{ route('reports.dashboard') }}" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="account_id" class="form-label">Account</label>
                        <select name="account_id" id="account_id" class="form-select">
                            <option value="">All Accounts</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected($account->id == $filters['account_id'])>
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <span class="tf-icons bx bx-search"></span> Filter
                        </button>
                        <a href="{{ route('reports.dashboard') }}" class="btn btn-outline-secondary">
                            <span class="tf-icons bx bx-refresh"></span> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Accounts Report</h5>
            <small class="text-muted">Last updated: {{ $lastUpdate }}</small>
        </div>

        {{-- O layout Sneat usa 'table-responsive text-nowrap' para tabelas --}}
        <div class="table-responsive text-nowrap">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Account Name</th>
                        <th class="text-end">Total Inflow</th>
                        <th class="text-end">Total Outflow</th>
                        <th class="text-end">Total Fees</th>
                        <th class="text-end">Total Costs</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($reports as $report)
                        <tr>
                            {{-- Use 'fw-bold' para dar destaque ao nome --}}
                            <td><span class="fw-bold">{{ $report->account_name }}</span></td>
                            <td class="text-end">{{ number_format($report->total_in, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($report->total_out, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($report->total_fees, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($report->total_costs, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                No data found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Adiciona a paginação dentro do card-footer se ela existir --}}
        @if ($reports->hasPages())
            <div class="card-footer">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

</div>
@endsection