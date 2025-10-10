<div class="user-behavior-detail">
    {{-- Cabeçalho com info do usuário --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1">{{ $data['user']['name'] }}</h5>
                    <p class="text-muted mb-0">Document: {{ $data['user']['document'] }}</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-label-primary">{{ $data['summary']['associated_accounts'] }} Account(s)</span>
                    <p class="text-muted mb-0 mt-1">
                        <small>
                            Member since: {{ $data['summary']['first_transaction_date']?->format('d/m/Y') ?? 'N/A' }}
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs Principais --}}
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <small class="text-muted d-block">Total Transactions</small>
                            <h4 class="mb-0">{{ number_format($data['summary']['total_transactions']) }}</h4>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-receipt bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <small class="text-muted d-block">Total Deposits</small>
                            <h4 class="mb-0 text-success">{{ number_format($data['deposits']['total'], 2) }}</h4>
                            <small class="text-muted">{{ $data['deposits']['count'] }} transactions</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-down-arrow-alt bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <small class="text-muted d-block">Total Withdrawals</small>
                            <h4 class="mb-0 text-danger">{{ number_format($data['withdrawals']['total'], 2) }}</h4>
                            <small class="text-muted">{{ $data['withdrawals']['count'] }} transactions</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="bx bx-up-arrow-alt bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <small class="text-muted d-block">Net Balance</small>
                            <h4 class="mb-0 {{ $data['summary']['net_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['summary']['net_balance'], 2) }}
                            </h4>
                            <small class="text-muted">Deposits - Withdrawals</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded {{ $data['summary']['net_balance'] >= 0 ? 'bg-label-success' : 'bg-label-danger' }}">
                                <i class="bx bx-wallet bx-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Estatísticas Detalhadas --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Deposit Statistics</h5>
                    <i class="bx bx-trending-up text-success"></i>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Total Transactions:</td>
                                    <td class="text-end"><strong>{{ number_format($data['deposits']['count']) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Total Volume:</td>
                                    <td class="text-end"><strong>{{ number_format($data['deposits']['total'], 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Average Amount:</td>
                                    <td class="text-end"><strong>{{ number_format($data['deposits']['average'], 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Highest Deposit:</td>
                                    <td class="text-end text-success"><strong>{{ number_format($data['deposits']['max'], 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Lowest Deposit:</td>
                                    <td class="text-end"><strong>{{ number_format($data['deposits']['min'], 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Withdrawal Statistics</h5>
                    <i class="bx bx-trending-down text-danger"></i>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Total Transactions:</td>
                                    <td class="text-end"><strong>{{ number_format($data['withdrawals']['count']) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Total Volume:</td>
                                    <td class="text-end"><strong>{{ number_format($data['withdrawals']['total'], 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Average Amount:</td>
                                    <td class="text-end"><strong>{{ number_format($data['withdrawals']['average'], 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Highest Withdrawal:</td>
                                    <td class="text-end text-danger"><strong>{{ number_format($data['withdrawals']['max'], 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Lowest Withdrawal:</td>
                                    <td class="text-end"><strong>{{ number_format($data['withdrawals']['min'], 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transações por Conta --}}
    @if($data['accounts']->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Transactions by Account</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th class="text-center">Transactions</th>
                            <th class="text-end">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['accounts'] as $account)
                        <tr>
                            <td>{{ $account->account_name }}</td>
                            <td class="text-center">
                                <span class="badge bg-label-primary">{{ number_format($account->transaction_count) }}</span>
                            </td>
                            <td class="text-end">{{ number_format($account->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Distribuição de Status --}}
    @if($data['status_distribution']->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Transaction Status Distribution</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($data['status_distribution'] as $status)
                <div class="col-md-4 mb-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">{{ ucfirst($status->status) }}</h6>
                            <small class="text-muted">{{ number_format($status->count) }} transactions</small>
                        </div>
                        <div class="text-end">
                            <strong>{{ number_format($status->total_amount, 2) }}</strong>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Últimas Transações --}}
    @if($data['last_transactions']->count() > 0)
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Transactions (Last 10)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['last_transactions'] as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $transaction->account->name ?? 'N/A' }}</td>
                            <td>
                                @if($transaction->type_transaction === 'IN')
                                <span class="badge bg-label-success">Deposit</span>
                                @else
                                <span class="badge bg-label-danger">Withdrawal</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($transaction->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-label-{{ $transaction->status === 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($transaction->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>