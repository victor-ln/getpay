@extends('layouts/contentNavbarLayout')



@section('title', 'Generate New Take')

@section('content')
    <h1>Generate New Take</h1>
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
                        <th class="text-end">Total IN</th>
                        <th class="text-end">Total OUT</th>
                        <th class="text-end">Net Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportData as $account)
                        <tr>
                            <td>{{ $account->account_name }}</td>
                            <td class="text-end">R$ {{ number_format($account->total_in, 2, ',', '.') }}</td>
                            <td class="text-end">R$ {{ number_format($account->total_out, 2, ',', '.') }}</td>
                            <td class="text-end">R$ {{ number_format($account->total_profit, 2, ',', '.') }}</td>
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
                    <div class="row">
                        <div class="col-md-6">
                            <label for="source_bank_id" class="form-label"><strong>1. Withdraw from:</strong></label>
                            <select name="source_bank_id" id="source_bank_id" class="form-select" required>
                                <option value="">Select source account...</option>
                                @foreach ($sourceBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->nickname }} ({{ $bank->bank_name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="destination_payout_key_id" class="form-label"><strong>2. Send to PIX Key:</strong></label>
                            <select name="destination_payout_key_id" id="destination_payout_key_id" class="form-select" required>
                                <option value="">Select destination...</option>
                                @foreach ($destinations as $destination)
                                    <option value="{{ $destination->id }}">{{ $destination->nickname }} ({{ $destination->pix_key }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg">Confirm and Initiate Transfer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection