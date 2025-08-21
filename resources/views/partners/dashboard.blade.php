@extends('layouts/contentNavbarLayout')

@section('title', 'Partner Dashboard')

@section('content')
<div class="container-fluid py-4">

    {{-- ======================================================= --}}
    {{-- == SEÇÃO 1: BALANÇO DOS BANCOS DA EMPRESA == --}}
    {{-- ======================================================= --}}
    <h6 class="py-3 mb-4">
        <span class="text-muted fw-light">Dashboard /</span> Company Bank Balances
    </h6>
    @if(isset($banksWithBalance) && !empty($banksWithBalance))
    <div class="row">
        @foreach($banksWithBalance as $bank)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 {{ ($bank['status_code'] ?? 0) == 200 ? 'border-success' : 'border-danger' }}">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title mb-0">
                            {{ strtolower($bank['name']) == 'lumenpay 1' ? 'CONTA NV1' : 'CONTA NV3' }}
                        </h5>
                        @if(($bank['status_code'] ?? 0) == 200)
                        <span class="badge bg-success-subtle text-success rounded-pill">Online</span>
                        @else
                        <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill">Error</span>
                        @endif
                    </div>
                    @if(($bank['status_code'] ?? 0) == 200)
                    <div>
                        <h6 class="card-subtitle mb-2 text-muted">Current Balance</h6>
                        <p class="card-text h3 fw-bold text-success-emphasis">
                            R$ {{ number_format($bank['balance'], 2, ',', '.') }}
                        </p>
                    </div>
                    <div class="mt-3">
                        <h6 class="card-subtitle mb-2 text-muted">Provisioned Balance</h6>
                        <p class="card-text h5">
                            R$ {{ number_format($bank['balance_provisioned'], 2, ',', '.') }}
                        </p>
                    </div>
                    @else
                    <div class="text-center my-auto text-muted">
                        <p class="mt-2">Could not retrieve balance.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <hr class="my-5">

    {{-- ======================================================= --}}
    {{-- == SEÇÃO 2: MÉTRICAS GLOBAIS DO NEGÓCIO == --}}
    {{-- ======================================================= --}}
    <h6 class="py-3 mb-4">
        <span class="text-muted fw-light">Dashboard /</span> Global Metrics
    </h6>
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Balance in Custody</h5>
                    <p class="h2 text-primary fw-bold">R$ {{ number_format($totalBalanceInCustody ?? 0, 2, ',', '.') }}</p>
                    <small class="text-muted">Sum of all client account balances.</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Net Profit (Fee - Cost)</h5>
                    <p class="h2 text-success fw-bold">R$ {{ number_format($totalNetProfit ?? 0, 2, ',', '.') }}</p>
                    <small class="text-muted">Total profit after costs since the start date.</small>
                </div>
            </div>
        </div>
    </div>


    {{-- ======================================================= --}}
    {{-- == SEÇÃO 3: ANÁLISE DETALHADA POR CONTA DE CLIENTE == --}}
    {{-- ======================================================= --}}
    <h6 class="py-3 mb-4">
        <span class="text-muted fw-light">Dashboard /</span> Accounts Financial Summary
    </h6>
    @forelse($accountSummaries as $account)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <div>
                <h5 class="card-title mb-0">{{ $account->name }}</h5>
                <small class="text-muted">Account ID: {{ $account->id }}</small>
            </div>
            {{-- NOVO: Exibição do nome do Banco/Adquirente --}}
            @if($account->acquirer)
            <span class="badge bg-dark fs-6">
                {{ $account->acquirer->name == 'lumenpay 1' ? 'CONTA NV1' : 'CONTA NV3' }}
            </span>
            @endif
        </div>
        <div class="card-body">
            <div class="row">

                <div class="col-12 d-flex justify-content-around text-center mb-4 border-bottom pb-3 mt-4">
                    <div>
                        <h6 class="text-muted">Total available balance</h6>
                        <p class="h4 fw-bold">R$ {{ number_format(($account->balance->available_balance ?? 0) + ($account->balance->blocked_balance ?? 0), 2, ',', '.') }}</p>
                    </div>
                    @php
                    $lucroIn = ($account->fee_in ?? 0) - ($account->cost_in ?? 0);
                    $lucroOut = ($account->fee_out ?? 0) - ($account->cost_out ?? 0);
                    $totalProfit = $lucroIn + $lucroOut;
                    @endphp
                    <div>
                        <h6 class="text-muted">Total Profit for Division</h6>
                        <p class="h4 fw-bold text-success">R$ {{ number_format($totalProfit, 2, ',', '.') }}</p>
                    </div>
                </div>
                {{-- SEÇÃO PAY IN --}}
                <div class="col-md-6 border-end p-3">
                    <h6 class="text-muted text-center mb-3">PAY IN</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Total Amount Received:</span>
                            <span class="fw-bold text-success">R$ {{ number_format($account->total_in ?? 0, 2, ',', '.') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Fees Charged (Fee):</span>
                            <span>R$ {{ number_format($account->fee_in ?? 0, 2, ',', '.') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Operational Cost (Cost):</span>
                            <span>R$ {{ number_format($account->cost_in ?? 0, 2, ',', '.') }}</span>
                        </li>
                        @php $lucroIn = ($account->fee_in ?? 0) - ($account->cost_in ?? 0); @endphp
                        <li class="list-group-item d-flex justify-content-between bg-primary-subtle mt-2 rounded">
                            <strong class="text-primary">Profit for Division (IN):</strong>
                            <strong class="text-primary">R$ {{ number_format($lucroIn, 2, ',', '.') }}</strong>
                        </li>
                    </ul>
                </div>

                {{-- SEÇÃO PAY OUT --}}
                <div class="col-md-6 p-3">
                    <h6 class="text-muted text-center mb-3">PAY OUT</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Total Amount Sent:</span>
                            <span class="fw-bold text-secondary">R$ {{ number_format($account->total_out ?? 0, 2, ',', '.') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Fees Charged (Fee):</span>
                            <span>R$ {{ number_format($account->fee_out ?? 0, 2, ',', '.') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Operational Cost (Cost):</span>
                            <span>R$ {{ number_format($account->cost_out ?? 0, 2, ',', '.') }}</span>
                        </li>
                        @php $lucroOut = ($account->fee_out ?? 0) - ($account->cost_out ?? 0); @endphp
                        <li class="list-group-item d-flex justify-content-between bg-primary-subtle mt-2 rounded">
                            <strong class="text-primary">Profit for Division (OUT):</strong>
                            <strong class="text-primary">R$ {{ number_format($lucroOut, 2, ',', '.') }}</strong>
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="my-3">

            <h6 class="card-subtitle text-muted">Current Fee Rules</h6>
            <div class="row mt-2">
                @php
                $feeIn = $account->feeProfiles->where('pivot.transaction_type', 'IN')->where('pivot.status', 'active')->first();
                $feeOut = $account->feeProfiles->where('pivot.transaction_type', 'OUT')->where('pivot.status', 'active')->first();
                $feeDefault = $account->feeProfiles->where('pivot.transaction_type', 'DEFAULT')->where('pivot.status', 'active')->first();
                @endphp

                {{-- Card para a taxa IN --}}
                <div class="col-md-6 mb-3">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <h5 class="card-title">IN Fee</h5>
                            @if($feeIn || $feeDefault)
                            @php $activeFee = $feeIn ?: $feeDefault; @endphp
                            <p class="mb-1"><strong>Profile:</strong> {{ $activeFee->name }} @if(!$feeIn && $feeDefault)<small class="text-muted">(Default)</small>@endif</p>
                            <hr class="my-2">
                            @switch($activeFee->calculation_type)
                            @case('SIMPLE_FIXED')
                            <p class="mb-1"><strong>Fixed Fee:</strong> R$ {{ number_format($activeFee->fixed_fee, 2, ',', '.') }}</p>
                            @break
                            @case('GREATER_OF_BASE_PERCENTAGE')
                            <p class="mb-1"><strong>Base Fee:</strong> R$ {{ number_format($activeFee->base_fee, 2, ',', '.') }}</p>
                            <p class="mb-0"><strong>Percentage:</strong> {{ number_format($activeFee->percentage_fee, 2, ',', '.') }}%</p>
                            @break
                            @case('TIERED')
                            <p class="mb-1"><strong>Type:</strong> Tiered Pricing</p>
                            {{-- Loop para exibir cada faixa de valor --}}
                            @forelse($activeFee->tiers->sortBy('min_value') as $tier)
                            <div class="ps-2 small border-start ms-1 mt-2">
                                <span>
                                    R$ {{ number_format($tier->min_value, 2, ',', '.') }} to {{ $tier->max_value ? 'R$ ' . number_format($tier->max_value, 2, ',', '.') : 'Above' }}:
                                </span>
                                <strong class="ms-1">
                                    @if(!is_null($tier->fixed_fee)) R$ {{ number_format($tier->fixed_fee, 2, ',', '.') }} @endif
                                    @if(!is_null($tier->fixed_fee) && !is_null($tier->percentage_fee)) + @endif
                                    @if(!is_null($tier->percentage_fee)) {{ number_format($tier->percentage_fee, 2, ',', '.') }}% @endif
                                </strong>
                            </div>
                            @empty
                            <p class="small text-muted ps-2">- No tiers configured.</p>
                            @endforelse
                            @break
                            @endswitch
                            @else
                            <p class="text-muted">No specific IN or DEFAULT fee assigned.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card para a taxa OUT --}}
                <div class="col-md-6 mb-3">
                    <div class="card bg-light h-100">
                        <div class="card-body">
                            <h5 class="card-title">OUT Fee</h5>
                            @if($feeOut || $feeDefault)
                            @php $activeFee = $feeOut ?: $feeDefault; @endphp
                            <p class="mb-1"><strong>Profile:</strong> {{ $activeFee->name }} @if(!$feeOut && $feeDefault)<small class="text-muted">(Default)</small>@endif</p>
                            <hr class="my-2">
                            @switch($activeFee->calculation_type)
                            @case('SIMPLE_FIXED')
                            <p class="mb-1"><strong>Fixed Fee:</strong> R$ {{ number_format($activeFee->fixed_fee, 2, ',', '.') }}</p>
                            @break
                            @case('GREATER_OF_BASE_PERCENTAGE')
                            <p class="mb-1"><strong>Base Fee:</strong> R$ {{ number_format($activeFee->base_fee, 2, ',', '.') }}</p>
                            <p class="mb-0"><strong>Percentage:</strong> {{ number_format($activeFee->percentage_fee, 2, ',', '.') }}%</p>
                            @break
                            @case('TIERED')
                            <p class="mb-0"><strong>Type:</strong> Tiered Pricing ({{ $activeFee->tiers->count() }} tiers)</p>
                            @break
                            @endswitch
                            @else
                            <p class="text-muted">No specific OUT or DEFAULT fee assigned.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="alert alert-info">No accounts with the specified criteria found.</div>
    @endforelse
</div>
@endsection