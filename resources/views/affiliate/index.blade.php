@extends('layouts/contentNavbarLayout')

@section('title', 'Affiliate Dashboard')

@vite(['resources/assets/js/affiliate.js'])

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Dashboard /</span> Affiliate
</h4>

{{-- 1. Card do Link de Referência --}}
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Your Referral Link</h5>
        <p>Share this link to refer new accounts and earn commissions.</p>
        <div class="input-group">
            <input type="text" id="referralLinkInput" class="form-control" value="{{ $referralLink }}" readonly>
            <button class="btn btn-outline-primary" type="button" id="copyReferralLinkBtn">
                <i class="bx bx-copy"></i>
            </button>
        </div>
    </div>
</div>

{{-- 2. Cards de KPIs --}}
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Total Referred Accounts</h6>
                <h4 class="mb-0">{{ $kpis['total_referred'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Referred Volume (Month)</h6>
                <h4 class="mb-0">R$ {{ number_format($kpis['referred_volume_month'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Commission (Month)</h6>
                <h4 class="mb-0 text-success">R$ {{ number_format($kpis['commission_month'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-1">Commission (Today)</h6>
                <h4 class="mb-0 text-success">R$ {{ number_format($kpis['commission_today'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- 3. Tabela de Contas Indicadas --}}
<div class="card">
    <h5 class="card-header">Your Referred Accounts</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Account Name</th>
                    <th>Status</th>
                    <th>Registered On</th>
                    <th class="text-end">Volume (This Month)</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($referredAccounts as $account)
                <tr>
                    <td><strong>{{ $account->name }}</strong></td>
                    <td>
                        @if($account->status)
                        <span class="badge bg-label-success">Active</span>
                        @else
                        <span class="badge bg-label-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $account->created_at->format('Y-m-d') }}</td>
                    <td class="text-end">R$ {{-- {{ $account->monthly_volume ?? '0.00' }} --}}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center p-4">
                        <p class="mb-0">You haven't referred any accounts yet.</p>
                        <small>Share your link to get started!</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection