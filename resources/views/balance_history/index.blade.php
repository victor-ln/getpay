@extends('layouts/contentNavbarLayout')

@section('title', 'Balance Statement')

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Account /</span> Balance Statement
</h4>

{{-- ✅ PAINEL DE FILTROS COMPLETO --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('balance.history') }}" class="row g-3 align-items-end">

            {{-- Filtro de Conta (Apenas para Admins) --}}
            @if (Auth::user()->isAdmin() && $accountsForSelector->isNotEmpty())
            <div class="col-md-3">
                <label for="account_id" class="form-label">Account</label>
                <select name="account_id" class="form-select">
                    @foreach ($accountsForSelector as $account)
                    <option value="{{ $account->id }}" @selected($selectedAccount->id == $account->id)>
                        {{ $account->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-2">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-2">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    <option value="credit" @selected(request('type')=='credit' )>Credit</option>
                    <option value="debit" @selected(request('type')=='debit' )>Debit</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Description, name...">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('balance.history') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Statement for: {{ $selectedAccount->name }}</h5>
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Client / Details</th>
                    @if (Auth::user()->isAdmin())
                    <th>Acquirer</th>
                    @endif
                    <th class="text-end">Amount</th>
                    <th class="text-end">Resulting Balance</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($balanceHistory as $entry)
                <tr>
                    <td>
                        <strong>{{ $entry->created_at->format('Y-m-d') }}</strong>
                        <small class="d-block text-muted">{{ $entry->created_at->format('H:i:s') }}</small>
                    </td>
                    <td>{{ $entry->description }}</td>
                    <td>
                        @if($entry->payment)
                        <strong>{{ $entry->payment->name ?? 'N/A' }}</strong>
                        @php
                        $doc = $entry->payment->document ? preg_replace('/[^0-9]/', '', $entry->payment->document ) : null;
                        $masked = 'N/A';

                        if ($doc) {
                        if (strlen($doc) === 11) {
                        $masked = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '***.$2.***-**', $doc);
                        } elseif (strlen($doc) === 14) {
                        $masked = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '**.$2.***/****-**', $doc);
                        } else {
                        $masked = '***' . substr($doc, -4);
                        }
                        }
                        @endphp

                        <small class="d-block text-muted">{{ $masked}}</small>
                        @else
                        <span class="text-muted">System Operation</span>
                        @endif
                    </td>
                    @if (Auth::user()->isAdmin())
                    <td>
                        @if($entry->bank)
                        <span class="badge bg-label-info">{{ $entry->bank->name }}</span>
                        @else
                        <span class="badge bg-label-secondary">N/A</span>
                        @endif
                    </td>
                    @endif
                    <td class="text-end">
                        @if ($entry->type === 'credit')
                        <span class="text-success fw-bold">+ R$ {{ number_format($entry->amount, 2, ',', '.') }}</span>
                        @else
                        <span class="text-danger fw-bold">- R$ {{ number_format(abs($entry->amount), 2, ',', '.') }}</span>
                        @endif
                    </td>
                    <td class="text-end">R$ {{ number_format($entry->balance_after, 2, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center p-5">
                        <h5 class="mb-2">No Transactions Found</h5>
                        <p class="text-muted">There is no balance history for this account matching your criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($balanceHistory->hasPages())
    <div class="card-footer d-flex justify-content-center">
        {{ $balanceHistory->links() }}
    </div>
    @endif
</div>
@endsection