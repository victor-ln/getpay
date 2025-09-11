@extends('layouts/contentNavbarLayout')

@section('title', 'Take Details #' . $take->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold py-3 mb-0">
        <span class="text-muted fw-light">
            <a href="{{ route('admin.takes.index') }}">Take History</a> /
        </span>
        Take Details #{{ $take->id }}
    </h5>
    <a href="{{ route('admin.takes.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-arrow-back me-1"></i> Back to History
    </a>
</div>

{{-- TAKE SUMMARY --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title m-0">Period Summary</h5>
        <small class="text-muted">
            From: {{ $take->start_date->format('d-m-Y H:i') }} | To: {{ $take->end_date->format('d-m-Y H:i') }}
        </small>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="bg-light p-3 rounded">
                    <small class="text-muted d-block mb-1">Total Net Profit</small>
                    <h5 class="mb-0 text-success">$ {{ number_format($take->total_net_profit, 2, '.', ',') }}</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light p-3 rounded">
                    <small class="text-muted d-block mb-1">Total Fees (IN + OUT)</small>
                    <h5 class="mb-0">$ {{ number_format($take->total_fees_in + $take->total_fees_out, 2, '.', ',') }}</h5>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-light p-3 rounded">
                    <small class="text-muted d-block mb-1">Total Costs (IN + OUT)</small>
                    <h5 class="mb-0 text-danger">$ {{ number_format($take->total_costs_in + $take->total_costs_out, 2, '.', ',') }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- DETAILED REPORT BY ACCOUNT --}}
<div class="card">
    <h5 class="card-header">Detailed Report by Account</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Client Account</th>
                    <th class="text-end">Volume IN</th>
                    <th class="text-end">Fees IN</th>
                    <th class="text-end">Volume OUT</th>
                    <th class="text-end">Fees OUT</th>
                    <th class="text-end">Net Profit</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($take->report_data as $reportItem)
                <tr>
                    <td><strong>{{ $reportItem['account_name'] }}</strong></td>
                    <td class="text-end">$ {{ number_format($reportItem['volume_in'], 2, '.', ',') }}</td>
                    <td class="text-end">$ {{ number_format($reportItem['fees_in'], 2, '.', ',') }}</td>
                    <td class="text-end">$ {{ number_format($reportItem['volume_out'], 2, '.', ',') }}</td>
                    <td class="text-end">$ {{ number_format($reportItem['fees_out'], 2, '.', ',') }}</td>
                    <td class="text-end"><strong class="text-primary">$ {{ number_format($reportItem['net_profit'], 2, '.', ',') }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center p-4">
                        No detailed report data available for this Take.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection