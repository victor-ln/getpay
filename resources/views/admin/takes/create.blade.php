@extends('layouts/contentNavbarLayout')



@section('title', 'Generate New Take')

@vite('resources/assets/js/no-double-click.js')

@section('content')
<h5>Generate New Take</h5>
<p class="text-muted">
    Processing transactions from <strong>{{ $startDate->format('d/m/Y H:i') }}</strong> to <strong>{{ $endDate->format('d/m/Y H:i') }}</strong>
</p>

{{-- CARD COM O LUCRO TOTAL --}}
<div class="card bg-light mb-4">
    <div class="card-body text-center">
        <h6 class="card-title text-uppercase">Total Net Profit in Period</h6>
        <p class="card-text h1 text-success">R$ {{ number_format($totalProfit, 2, ',', '.') }}</p>
    </div>
</div>

{{-- RELATÓRIO DETALHADO POR CONTA --}}
<h4 class="mt-5">Detailed Report by Account</h4>
<div class="card mb-4">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th class="text-end">Total IN (Volume)</th>
                    <th class="text-end">Total Fee</th>
                    <th class="text-end">Total Cost</th>
                    <th class="text-end">Total Profit</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reportData as $account)
                <tr>
                    <td>{{ $account->account_name }}</td>
                    <td class="text-end">R$ {{ number_format($account->total_in, 2, ',', '.') }}</td>
                    <td class="text-end">R$ {{ number_format($account->total_fee, 2, ',', '.') }}</td>
                    <td class="text-end text-danger">R$ {{ number_format($account->total_cost, 2, ',', '.') }}</td>
                    <td class="text-end text-danger">R$ {{ number_format(($account->total_fee - $account->total_cost), 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No transactions to process in this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- SÓ MOSTRA O FORMULÁRIO DE EXECUÇÃO SE HOUVER LUCRO --}}
@if ($totalProfit > 0)
<h4 class="mt-5">Execute Payout</h4>
<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.takes.store') }}" method="POST">
            @csrf
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Withdraw from (Source Bank)</th>
                            <th class="text-end">Profit to Withdraw</th>
                            <th>Send to PIX Key (Destination)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Loop sobre os lucros de cada adquirente --}}
                        @foreach ($payoutsByAcquirer as $index => $payout)
                        
                        @if($payout->profit > 0)
                        <tr>
                            <td>
                                <strong>{{ $payout->acquirer_name }}</strong>
                                {{-- Enviamos o ID do banco e o valor como campos escondidos --}}
                                <input type="hidden" name="payouts[{{ $index }}][source_bank_id]" value="{{ $payout->acquirer_id }}">
                                <input type="hidden" name="payouts[{{ $index }}][amount]" value="{{ $payout->profit }}">
                            </td>
                            <td class="text-end">
                                <strong>R$ {{ number_format($payout->profit, 2, ',', '.') }}</strong>
                            </td>
                            <td>
                                <select name="payouts[{{ $index }}][destination_id]" class="form-select" required>
                                    <option value="">Select destination...</option>
                                    @foreach ($destinations as $destination)
                                    <option value="{{ $destination->id }}">{{ $destination->nickname }} ({{ $destination->pix_key }})</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary btn-lg">Confirm and Initiate All Transfers</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection