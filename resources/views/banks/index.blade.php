@extends('layouts/contentNavbarLayout')

@section('title', 'Banks & Acquirers')

@section('page-script')
@vite(['resources/assets/js/delete-item.js', 'resources/assets/js/bank-details.js'])
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-3 mb-0">
        <span class="text-muted fw-light">Admin /</span> Banks & Acquirers
    </h4>
    <a href="{{ route('banks.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Add Bank
    </a>
</div>

<div class="card">
    <div class="card-header">
        {{-- ESTRUTURA DE ABAS --}}
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item">
                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#active-banks-tab" role="tab" aria-selected="true">
                    Active Banks &nbsp; <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-success">{{ $activeBanks->count() }}</span>
                </button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#inactive-banks-tab" role="tab" aria-selected="false">
                    Inactive Banks &nbsp;<span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-secondary">{{ $inactiveBanks->count() }}</span>
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content p-0">
            {{-- ABA DE BANCOS ATIVOS --}}
            <div class="tab-pane fade show active" id="active-banks-tab" role="tabpanel">
                @include('banks._table', ['banks' => $activeBanks])
            </div>

            {{-- ABA DE BANCOS INATIVOS --}}
            <div class="tab-pane fade" id="inactive-banks-tab" role="tabpanel">
                @include('banks._table', ['banks' => $inactiveBanks])
            </div>
        </div>
    </div>
</div>


{{-- MODAL DE DETALHES (genérico, será preenchido por JavaScript) --}}
<div class="modal fade" id="bankDetailsModal" tabindex="-1" aria-labelledby="bankDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bankDetailsModalLabel">Bank Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Estado de Carregamento --}}
                <div id="modal-loading-state" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                {{-- Conteúdo (começa escondido) --}}
                <div id="modal-content-state" class="d-none">

                    <div class="mb-3 text-center bg-primary text-white p-3 rounded">
                        <small class="d-block mb-1" style="opacity: 0.8;">ACQUIRER REAL-TIME BALANCE</small>
                        <h3 class="mb-0 text-white">R$ <span id="modal-acquirer-balance">--</span></h3>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Total Custody (Available + Blocked)</small>
                        <h4 class="mb-0">R$ <span id="modal-bank-custody">--</span></h4>
                    </div>
                    <hr>
                    <div>
                        <small class="text-muted d-block mb-2">Clients using this bank as default</small>
                        <div id="modal-client-list">
                            {{-- A lista de clientes com seus saldos será inserida aqui pelo JavaScript --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection