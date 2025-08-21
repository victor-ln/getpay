@extends('layouts/contentNavbarLayout')

@section('title', 'Transactions')

@section('vendor-style')
{{-- Se você usar um date range picker como Flatpickr, adicione o CSS dele aqui --}}
{{-- Exemplo:
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/flatpickr/flatpickr.css') }}" /> --}}
@endsection

@section('page-style')
{{-- Estilos específicos da página, se necessário --}}
@endsection

@section('content')
<h4 class="py-3 mb-4">
    Transactions
</h4>

<div class="card">
    <div class="card-header">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-0">Last transactions</h5>
            <div>
                {{-- O formulário de exportação pode ser um GET para a mesma rota com um parâmetro extra, ou uma rota
                dedicada --}}
                <a href="{{ route('transactions.index', array_merge(request()->query(), ['export' => 'xls'])) }}"
                    class="btn btn-success me-2">
                    <i class="bx bx-export me-1"></i> Export to XLS
                </a>
                {{-- Você pode adicionar um botão para "Limpar Filtros" aqui se desejar --}}
            </div>
        </div>
    </div>

    {{-- Filtros de Pesquisa --}}
    <div class="card-body">
        <form id="transactionFilterForm" action="{{ route('transactions.index') }}" method="GET" style="display: none">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="filter_gpid" class="form-label">GPID</label>
                    <input type="text" class="form-control" id="filter_gpid" name="filter_gpid"
                        value="{{ request('filter_gpid') }}" placeholder="Transaction ID">
                </div>
                <div class="col-md-3">
                    <label for="filter_external_id" class="form-label">External ID</label>
                    <input type="text" class="form-control" id="filter_external_id" name="filter_external_id"
                        value="{{ request('filter_external_id') }}" placeholder="External Payment ID">
                </div>
                <div class="col-md-3">
                    <label for="filter_client_name" class="form-label">Client Name</label>
                    <input type="text" class="form-control" id="filter_client_name" name="filter_client_name"
                        value="{{ request('filter_client_name') }}" placeholder="Client's Name">
                </div>
                <div class="col-md-3">
                    <label for="filter_type" class="form-label">Type</label>
                    <select class="form-select" id="filter_type" name="filter_type">
                        <option value="">All Types</option>
                        <option value="IN" {{ request('filter_type')=='IN' ? 'selected' : '' }}>Pay In</option>
                        <option value="OUT" {{ request('filter_type')=='OUT' ? 'selected' : '' }}>Pay Out</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label">Status</label>
                    <select class="form-select" id="filter_status" name="filter_status">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('filter_status')=='pending' ? 'selected' : '' }}>Pending
                        </option>
                        <option value="paid" {{ request('filter_status')=='paid' ? 'selected' : '' }}>Approved</option>
                        {{-- "paid" é geralmente o valor no backend para "Approved" --}}
                        <option value="canceled" {{ request('filter_status')=='canceled' ? 'selected' : '' }}>Declined
                        </option>
                        <option value="refused" {{ request('filter_status')=='refused' ? 'selected' : '' }}>Refused
                        </option> {{-- Ajuste se os status forem diferentes --}}
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_start_date" class="form-label">Start Date</label>
                    <input type="text" class="form-control datepicker" id="filter_start_date" name="filter_start_date"
                        value="{{ request('filter_start_date') }}" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-3">
                    <label for="filter_end_date" class="form-label">End Date</label>
                    <input type="text" class="form-control datepicker" id="filter_end_date" name="filter_end_date"
                        value="{{ request('filter_end_date') }}" placeholder="YYYY-MM-DD">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bx bx-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-eraser me-1"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover" style="font-size: 12px">
            <thead>
                <tr>
                    <th>GPID</th>
                    <th>External ID</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Fee</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    @if (Auth::check() && Auth::user()->level == 'admin')
                    <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($transactions as $transaction)
                <tr>
                    <td><strong>{{ $transaction->id }}</strong></td>
                    <td>{{ $transaction->external_payment_id ?? 'N/A' }}</td>
                    <td>
                        @if ($transaction->user)
                        <a href="{{ route('users.edit', $transaction->user->id) }}">
                            {{ $transaction->user->name }}
                        </a>
                        @else
                        N/A
                        @endif
                    </td>
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
                        @php
                        $statusClass = 'bg-label-secondary'; // Default
                        $statusText = Str::ucfirst($transaction->status);
                        if ($transaction->status == 'pending' || $transaction->status == 'processing') {
                        $statusClass = 'bg-label-warning'; $statusText = 'Pending';
                        } elseif ($transaction->status == 'paid') {
                        $statusClass = 'bg-label-success'; $statusText = 'Approved';
                        } elseif ($transaction->status == 'canceled') {
                        $statusClass = 'bg-label-danger'; $statusText = 'Declined';
                        } elseif ($transaction->status == 'refused') { // Adicionado para cobrir seu 'else' anterior
                        $statusClass = 'bg-label-danger'; $statusText = 'Refused';
                        }
                        @endphp
                        <span class="badge {{ $statusClass }} me-1">{{ $statusText }}</span>
                    </td>
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

    {{-- Links de Paginação (mantendo os filtros aplicados) --}}
    @if ($transactions->hasPages())
    <div class="card-footer d-flex justify-content-center">
        {{ $transactions->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection

@section('page-script')
@vite(['resources/assets/js/ui-toasts.js']) {{-- Se você usar toasts para notificações --}}
{{-- Se você usar um date range picker como Flatpickr, inicialize-o aqui --}}

@endsection