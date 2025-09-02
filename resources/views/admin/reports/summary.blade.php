@extends('layouts/contentNavbarLayout')

@section('title', 'Test Report - Summary')

@section('content')
    <h1>Summary Report</h1>
    <p>
        <strong>Period:</strong> {{ $currentStartDate->format('d/m/Y H:i') }}
        <strong>to</strong> {{ $currentEndDate->format('d/m/Y H:i') }}
    </p>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th class="text-end">Total IN</th>
                        <th class="text-end">Total OUT</th>
                        <th class="text-end">Total Profit (vs. Previous Period)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report as $accountData)
                        <tr>
                            <td>{{ $accountData->account_name }}</td>
                            <td class="text-end">R$ {{ number_format($accountData->total_in, 2, ',', '.') }}</td>
                            <td class="text-end">R$ {{ number_format($accountData->total_out, 2, ',', '.') }}</td>
                            <td class="text-end">
                                <strong>R$ {{ number_format($accountData->total_profit, 2, ',', '.') }}</strong>

                                @if(is_numeric($accountData->profit_percentage_change))
                                    @php
                                        $change = $accountData->profit_percentage_change;
                                        $colorClass = $change >= 0 ? 'text-success' : 'text-danger';
                                        $icon = $change >= 0 ? '▲' : '▼';
                                    @endphp
                                    <small class="{{ $colorClass }}">
                                        ({{ $icon }} {{ number_format(abs($change), 1, ',', '.') }}%)
                                    </small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No transactions found in the specified period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection