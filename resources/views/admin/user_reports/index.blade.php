@extends('layouts/contentNavbarLayout')

@section('title', 'User Behavior Reports')
@vite(['resources/assets/js/users-reports.js'])

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Admin /</span> User Reports
</h4>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item">
                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#by-account-tab" role="tab" aria-selected="true">
                    Report by Account
                </button>
            </li>

            <li class="nav-item">
                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#individual-analysis-tab" role="tab" aria-selected="false">
                    Individual Analysis
                </button>
            </li>

            @if($isAdmin)
            <li class="nav-item">
                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#multi-account-tab" role="tab" aria-selected="false" data-url="{{ route('admin.user-reports.multi-account-data') }}">
                    Multi-Account Analysis
                </button>
            </li>
            @endif
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">
            {{-- Aba 1: Relatório por Conta --}}
            <div class="tab-pane fade show active" id="by-account-tab" role="tabpanel">
                @include('_partials.reports.by-account-table', ['data' => $byAccountData])
            </div>

            {{-- Aba 2: Análise Individual --}}
            <div class="tab-pane fade" id="individual-analysis-tab" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-md-8 offset-md-2">
                        <div class="input-group">
                            <input type="text"
                                class="form-control"
                                id="user-search-input"
                                placeholder="Search by name or document (CPF/CNPJ)..."
                                autocomplete="off">
                            <button class="btn btn-primary" type="button" id="search-user-btn">
                                <i class="bx bx-search"></i> Search
                            </button>
                        </div>
                        <div id="search-results" class="list-group mt-2" style="display: none;"></div>
                    </div>
                </div>

                <div id="user-behavior-content">
                    <div class="text-center text-muted py-5">
                        <i class="bx bx-search-alt bx-lg"></i>
                        <p class="mt-3">Search for a user to view their behavior analysis</p>
                    </div>
                </div>
            </div>

            {{-- Aba 3: Análise Multi-Contas --}}
            @if($isAdmin)
            <div class="tab-pane fade" id="multi-account-tab" role="tabpanel">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading analysis data...</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection