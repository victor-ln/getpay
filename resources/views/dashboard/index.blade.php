@extends('layouts/contentNavbarLayout')

@section('title', 'GetPay - Dashboard')

{{-- Estilos e Scripts (sem alterações) --}}
@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection
@section('page-style')
@vite('resources/assets/css/banking.css')
@endsection
@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection
@section('page-script')
{{-- Seu banking.js (agora mais enxuto) será carregado aqui --}}
@vite('resources/assets/js/banking.js')
@vite('resources/assets/js/metrics.js')
@vite('resources/assets/js/dateRangeFilter.js')
@endsection
 
@section('content')

{{-- PAINEL DE SELEÇÃO DE CONTA (Funcionalidade mantida) --}}
@if(Auth::user()->level === 'admin' || Auth::user()->level === 'partner')
<div class="card mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('dashboard.select-account') }}">
            @csrf
            <div class="input-group">
                <label class="input-group-text" for="account-selector">Viewing Account</label>
                <select class="form-select" name="account_id" id="account-selector" onchange="this.form.submit()">
                    <option disabled>Select an account...</option>
                    @foreach($accountsForSelector as $account)
                    <option value="{{ $account->id }}" @selected($selectedAccount && $selectedAccount->id == $account->id)>
                        {{ $account->name }} (ID: {{ $account->id }})
                    </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>
@endif

{{-- MENSAGEM DE CONTA NÃO SELECIONADA (Funcionalidade mantida) --}}
@if(!$selectedAccount)
<div class="alert alert-info">Please select an account to view details.</div>
@else

{{-- INÍCIO DO DASHBOARD --}}
<div id="client-dashboard-wrapper">
    <div class="row g-4">
        {{-- CARD DE SALDO (Estrutura original preservada) --}}
        <div class="col-lg-4 col-md-12">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title m-0">Balance Overview</h5>
                </div>
                <div class="card-body">
                    <small>Available for Withdrawal (from default acquirer)</small>
                    <h2 id="withdrawable-balance-value" data-balance="{{ $balanceData['withdrawable'] ?? 0 }}" class="display-6 fw-bold my-1">
                        R$ {{ number_format($balanceData['withdrawable'] ?? 0, 2, ',', '.') }}
                    </h2>



                    <div class="d-flex justify-content-between mt-3">
                        <span>Blocked Balance</span>
                        <span>R$ {{ number_format($balanceData['blocked'] ?? 0, 2, ',', '.') }}</span>
                    </div>

                    <div class="d-flex justify-content-between mt-1">
                        <span>Frozen from migration account</span>
                        <span>R$ {{ number_format($balanceData['other_active'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total Balance</span>
                        <span>R$ {{ number_format($balanceData['total'] ?? 0, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD DE DEPÓSITO (Estrutura original de 2 ETAPAS restaurada) --}}
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title m-0">Fund Your Account</h5>
                </div>
                <div class="card-body">
                    {{-- Etapa 1: Formulário de Depósito (Controlado pelo JS) --}}
                    <div id="deposit-step-1">
                        <form id="generate-charge-form" data-user-document="{{ Auth::user()->document }}" data-user-name="{{ Auth::user()->name }}">
                            <div class="mb-3">
                                <label for="deposit-amount" class="form-label">Amount to Deposit (BRL)</label>
                                <input type="number" class="form-control" id="deposit-amount" placeholder="50.00" min="{{ $selectedAccount->minTransactionValue ?? 1 }}" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="deposit-description" class="form-label">Description (optional)</label>
                                <input type="text" class="form-control" id="deposit-description">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Generate PIX Charge</button>
                        </form>
                    </div>
                    {{-- Etapa 2: Exibição do PIX (Controlado pelo JS) --}}
                    <div id="deposit-step-2" class="d-none text-center">
                        <h6 id="pix-amount-display"></h6>
                        <img src="" id="pix-qr-code" class="img-fluid my-3" alt="PIX QR Code">
                        <input type="text" readonly class="form-control text-center" id="pix-copy-paste-code">
                        <button class="btn btn-sm btn-outline-secondary mt-2" id="copy-pix-code-btn">Copy Code</button>
                        <hr>
                        <button class="btn btn-secondary" id="create-new-deposit-btn">Create New Deposit</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- == CARD DE SAQUE (COM A ESTRUTURA CORRIGIDA) == --}}
        {{-- ======================================================= --}}
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title m-0">Withdraw Funds</h5>
                </div>
                <div class="card-body">
                    @if(Auth::user()->two_factor_enabled)
                    <div id="withdraw-details">
                        {{-- ETAPA 1: Formulário de Saque (visível inicialmente) --}}
                        <form id="withdraw-step-1-form">
                            <div class="mb-3">
                                <label for="withdraw-amount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="withdraw-amount" placeholder="50.00" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="pix-key-select" class="form-label">To (Registered PIX Key)</label>
                                <select class="form-select" id="pix-key-select" required @if($pixKeys->isEmpty()) disabled @endif>
                                    <option value="" selected disabled>
                                        @if($pixKeys->isEmpty()) No keys registered @else Select a key... @endif
                                    </option>
                                    @foreach($pixKeys as $key)
                                    <option value="{{ $key->key }}" data-type="{{ $key->type }}">{{ $key->type }}: {{ $key->key }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="button" class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#addPixKeyModal">+ Add Key</button>
                            <button type="submit" class="btn btn-primary w-100 mt-3" @if($pixKeys->isEmpty()) disabled @endif>Proceed to Confirmation</button>
                        </form>

                        {{-- ETAPA 2: Confirmação com 2FA (escondida inicialmente) --}}
                        <div id="withdraw-step-2-confirmation" class="d-none">
                            <p id="confirmation-summary" class="text-center"></p>
                            <hr>
                            <form id="withdraw-step-2-form">
                                <label class="form-label">Enter your 6-digit 2FA Code to confirm:</label>
                                <div class="d-flex justify-content-between gap-2">
                                    <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                                    <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                                    <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                                    <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                                    <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                                    <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" id="cancel-withdrawal-btn" class="btn btn-secondary">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        Confirm Withdrawal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @else
                    <div id="withdraw-2fa-disabled-error" class="alert alert-danger">
                        Two-Factor Authentication (2FA) is required for withdrawals.
                        <a href="{{ route('users.edit', Auth::user()->id) }}" class="alert-link">Please enable it in your profile.</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <!-- Card único com métricas de transações confirmadas -->

    <div class="row g-4 mt-2">
        {{-- KPI Card 1: PAY IN --}}
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title m-0">Pay In (Today)</h5>
                    <span class="badge bg-success">IN</span>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Transactions:</span>
                            <span class="fw-bold">{{ $kpiIn['total_transactions'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Paid:</span>
                            <span class="fw-bold text-success">
                                {{ $kpiIn['paid_transactions'] }}
                                @if($kpiIn['total_transactions'] > 0)
                                ({{ number_format(($kpiIn['paid_transactions'] / $kpiIn['total_transactions']) * 100, 1) }}%)
                                @else
                                (0%)
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Volume Paid:</span>
                            <span class="fw-bold">R$ {{ number_format($kpiIn['paid_volume'], 2, ',', '.') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Fees (Paid):</span>
                            <span class="fw-bold">R$ {{ number_format($kpiIn['total_fees'], 2, ',', '.') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- KPI Card 2: PAY OUT --}}
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title m-0"> Pay Out (Today)</h5>
                    <span class="badge bg-danger">OUT</span>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Transactions:</span>
                            <span class="fw-bold">{{ $kpiOut['total_transactions'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Paid:</span>
                            <span class="fw-bold text-success">
                                {{ $kpiOut['paid_transactions'] }}
                                @if($kpiOut['total_transactions'] > 0)
                                ({{ number_format(($kpiOut['paid_transactions'] / $kpiOut['total_transactions']) * 100, 1) }}%)
                                @else
                                (0%)
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Volume Paid:</span>
                            <span class="fw-bold">R$ {{ number_format($kpiOut['paid_volume'], 2, ',', '.') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total Fees (Paid):</span>
                            <span class="fw-bold">R$ {{ number_format($kpiOut['total_fees'], 2, ',', '.') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- KPI Card 3: PROFIT SUMMARY (Apenas para Admins) --}}
        @if (Auth::user()->isAdmin() && $profitSummary)
        <div class="col-lg-4 col-md-12">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title m-0">Profit Summary (Today)</h5>
                </div>
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Fees (IN + OUT)</h6>
                    <p class="h4">R$ {{ number_format($profitSummary['total_fees'], 2, ',', '.') }}</p>
                    <hr>
                    <h6 class="text-muted">Net Profit (Fee - Cost)</h6>
                    <p class="h4 text-primary fw-bold">R$ {{ number_format($profitSummary['net_profit'], 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>





    {{-- TABELA DE TRANSAÇÕES (Refatorada com Blade) --}}
    <!-- Filtros Simples - Adicione antes da tabela -->

    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Recent Transactions</h5>
        </div>

        <div class="card-body border-top">
    <form method="GET" action="{{ route('dashboard') }}">
        {{-- Linha 1: Filtros Principais (Busca, Status, Tipo, Período) --}}
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="search" class="form-label small">Search</label>
                <input type="text" class="form-control form-control-sm" name="search"
                    value="{{ request('search') }}"
                    placeholder="ID, External ID, Provider ID, Name or Document">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label small">Status</label>
                <select class="form-select form-select-sm" name="status">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    {{-- Adicione outros status aqui --}}
                </select>
            </div>
            <div class="col-md-2">
                <label for="type_transaction" class="form-label small">Type</label>
                <select class="form-select form-select-sm" name="type_transaction">
                    <option value="">All Types</option>
                    <option value="IN" {{ request('type_transaction') == 'IN' ? 'selected' : '' }}>Pay In</option>
                    <option value="OUT" {{ request('type_transaction') == 'OUT' ? 'selected' : '' }}>Pay Out</option>
                </select>
            </div>
            <div class="col-md-4">
                {{-- Agrupando o Período e as Datas Customizadas --}}
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="date_filter_select" class="form-label small">Period</label>
                        <select class="form-select form-select-sm" name="date_filter" id="date_filter_select">
                            <option value="all" {{ request('date_filter', 'all') == 'all' ? 'selected' : '' }}>All Time</option>
                            <option value="1" {{ request('date_filter') == '1' ? 'selected' : '' }}>Last 24h</option>
                            <option value="7" {{ request('date_filter') == '7' ? 'selected' : '' }}>Last 7 days</option>
                            <option value="custom" {{ request('date_filter') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                            {{-- Adicione outros períodos aqui --}}
                        </select>
                    </div>
                    <div class="col-sm-6">
                        {{-- Container das datas customizadas --}}
                        <div id="custom_date_range_fields" style="display: none;">
                            <label class="form-label small">Custom Dates</label>
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                                <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Linha 2: Filtros Secundários (Valores) e Botões de Ação --}}
        <div class="row g-3 align-items-end mt-2">
            <div class="col-md-2">
                <label for="amount_min" class="form-label small">Min Amount</label>
                <input type="number" step="0.01" class="form-control form-control-sm" name="amount_min"
                    value="{{ request('amount_min') }}" placeholder="0.00">
            </div>
            <div class="col-md-2">
                <label for="amount_max" class="form-label small">Max Amount</label>
                <input type="number" step="0.01" class="form-control form-control-sm" name="amount_max"
                    value="{{ request('amount_max') }}" placeholder="0.00">
            </div>

            {{-- Coluna vazia para empurrar os botões para a direita --}}
            <div class="col-md-4"></div> 

            {{-- Botões de Ação --}}
            <div class="col-md-4 text-md-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bx bx-search me-1"></i>Filter
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-x me-1"></i>Clear
                </a>
                <a href="{{ route('dashboard.export', request()->query()) }}" class="btn btn-success btn-sm">
                    <i class="bx bx-spreadsheet me-1"></i>Export XLS
                </a>
            </div>
        </div>
    </form>
</div>
        <!-- Indicador de filtros ativos (opcional) -->
        @if(request()->hasAny(['status', 'type_transaction', 'date_filter', 'amount_min']))
        <div class="row m-3">
            <div class="col-12">
                <small class="text-muted">
                    <strong>Active filters:</strong>
                    @if(request('status'))
                    <span class="badge bg-light text-dark me-1">Status: {{ ucfirst(request('status')) }}</span>
                    @endif
                    @if(request('type_transaction'))
                    <span class="badge bg-light text-dark me-1">Type: {{ request('type_transaction') == 'IN' ? 'Pay In' : 'Pay Out' }}</span>
                    @endif
                    @if(request('date_filter') && request('date_filter') != 'all')
                    <span class="badge bg-light text-dark me-1">
                        Period: Last {{ request('date_filter') }} {{ request('date_filter') == '1' ? 'day' : 'days' }}
                    </span>
                    @endif
                    @if(request('amount_min'))
                    <span class="badge bg-light text-dark me-1">Min: BRL {{ number_format(request('amount_min'), 2) }}</span>
                    @endif
                    @if(request('amount_max'))
                    <span class="badge bg-light text-dark me-1">Max: BRL {{ number_format(request('amount_max'), 2) }}</span>
                    @endif
                </small>
            </div>
        </div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover" style="font-size: 12px">
                <thead>
                    <tr>
                        <th>GPID</th>
                        <th>Transaction ID</th>
                        <th>External ID</th>
                        @if (Auth::check() && Auth::user()->level == 'admin')
                        <th>Client</th>
                        @endif
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Fee</th>
                        <th>Status</th>
                        <th>Name</th>
                        <th>Document</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        @if (Auth::check() && Auth::user()->level == 'admin')
                        <th>Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($recentTransactions as $transaction)
                    <tr>
                        <td><strong>{{ $transaction->id }}</strong></td>
                        <td>{{ $transaction->provider_transaction_id ?? 'N/A' }}</td>
                        <td>{{ $transaction->external_payment_id ?? 'N/A' }}</td>
                        @if (Auth::check() && Auth::user()->level == 'admin')
                        <td>
                            @if ($transaction->user)
                            <a href="{{ route('users.edit', $transaction->user->id) }}">
                                {{ $transaction->user->name }}
                            </a>
                            @else
                            N/A
                            @endif
                        </td>
                        @endif
                        <td>
                            @if ($transaction->type_transaction == 'IN')
                            <span class="badge bg-label-primary me-1">Pay In</span>
                            @elseif ($transaction->type_transaction == 'OUT')
                            <span class="badge bg-label-warning me-1">Pay Out</span>
                            @else
                            <span class="badge bg-label-secondary me-1">{{ Str::ucfirst($transaction->type_transaction)
                            }}</span>
                            @endif
                        </td>
                        <td>BRL {{ number_format($transaction->amount, 2, '.', ',') }}</td>
                        <td>BRL {{ number_format($transaction->fee, 2, '.', ',') }}</td>
                        <td>
                            {{-- Status Bar --}}
                            <div class="status-bar {{ $transaction->status }}"></div>

                            {{-- Badge com ícone --}}
                            @php
                            $statusConfig = [
                            'paid' => ['class' => 'bg-success', 'icon' => 'bx-check-circle', 'text' => 'Paid'],
                            'pending' => ['class' => 'bg-warning', 'icon' => 'bx-time-five', 'text' => 'Pending'],
                            'processing' => ['class' => 'bg-warning', 'icon' => 'bx-time-five', 'text' => 'Pending'],
                            'cancelled' => ['class' => 'bg-danger', 'icon' => 'bx-x-circle', 'text' => 'Cancelled'],
                            'refunded' => ['class' => 'bg-danger', 'icon' => 'bx-block', 'text' => 'Refunded'],
                            ];
                            $config = $statusConfig[$transaction->status] ?? ['class' => 'bg-secondary', 'icon' => 'bx-help-circle', 'text' => Str::ucfirst($transaction->status)];
                            @endphp

                            <span class="badge {{ $config['class'] }} me-1 d-flex align-items-center gap-1" style="width: fit-content;">
                                <i class='bx {{ $config['icon'] }}'></i>
                                {{ $config['text'] }}
                            </span>
                        </td>
                        <td>{{ $transaction->name ?? 'N/A' }}</td>
                        <td>{{ $transaction->document ?? 'N/A' }}</td>
                        <td>{{ $transaction->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $transaction->updated_at->format('d/m/Y H:i:s') }}</td>
                        @if (Auth::check() && Auth::user()->level == 'admin')
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    {{-- Ajuste a rota para a edição da transação se existir --}}
                                    <a class="dropdown-item"
                                        href="{{-- route('transactions.edit', $transaction->id) --}}javascript:void(0);"><i
                                            class="bx bx-edit-alt me-1"></i> Edit</a>
                                    {{-- Ajuste a rota/lógica para a exclusão da transação se existir --}}
                                    <a class="dropdown-item" href="javascript:void(0);"><i class="bx bx-trash me-1"></i>
                                        Delete</a>
                                </div>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ (Auth::check() && Auth::user()->level == 'admin') ? 10 : 9 }}" class="text-center">
                            No transactions found matching your criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-center">
            {{ $recentTransactions->appends(request()->except('page'))->links() }}
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="refundModalLabel">Confirm Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="refund-form">
                <div class="modal-body">
                    <p>You are about to request a refund for transaction <strong id="refund-tx-id">#</strong> for the amount of <strong id="refund-amount">R$ 0,00</strong>.</p>

                    <div id="refund-alert-notice" class="alert alert-warning p-2 small" role="alert" style="display: none;">
                    </div>



                    <hr>
                    <label for="refund-2fa-code" class="form-label">Enter your 6-digit 2FA Code to confirm:</label>
                    <div class="d-flex justify-content-between gap-2">
                        <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                        <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                        <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                        <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                        <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                        <input type="text" inputmode="numeric" class="form-control form-control-lg text-center 2fa-input" maxlength="1" required>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addPixKeyModal" tabindex="-1" aria-labelledby="addPixKeyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPixKeyModalLabel">Add New Payout PIX Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Usamos a rota que já criamos para salvar a chave do sócio --}}
            <form id="formAddNewPixKeyInModal" action="{{ route('account-pix-keys.store') }}" method="POST">
                <div class="modal-body">
                    @csrf

                    <input type="text" id="account-selector-modal" name="account_id" value="{{ $selectedAccount->id }}" hidden>
                    <div class="mb-3">
                        <label class="form-label">Key Type</label>
                        <select name="type" class="form-select" required>
                            <option value="EMAIL">E-mail</option>
                            <option value="PHONE">Phone</option>
                            <option value="CPF">CPF</option>
                            <option value="CNPJ">CNPJ</option>
                            <option value="EVP">Random Key</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Key</label>
                        <input type="text" name="key" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="submitPixKeyForm" class="btn btn-primary">Save and Use Key</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection