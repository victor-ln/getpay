@extends('layouts/contentNavbarLayout')

@section('title', 'Partner Payout Organization')

@section('page-script')
@vite(['resources/assets/js/ui-toasts.js'])
@vite(['resources/assets/js/partner-payouts.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Partners /</span> Payout Organization
</h4>

{{-- Exibição de mensagens de sucesso/erro vindas do backend --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

{{-- Card de Resumo --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                    <span class="avatar-initial rounded-circle bg-label-success"><i class="bx bx-wallet fs-4"></i></span>
                </div>
                <div>
                    <h5 class="mb-0" id="net-for-distribution">{{ number_format($headerStats['netForDistribution'] ?? 0, 2, ',', '.') }} BRL</h5>
                    <small>Net for Distribution</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-4">
                <div class="text-end">
                    <h5 class="mb-0" id="total-percentage">{{ number_format($totalPercentageDistributed ?? 0, 2) }}%</h5>
                    <small>Total Distributed</small>
                </div>
                <button id="addPartnerBtnMain" class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
                    <i class="bx bx-plus me-1"></i> Add Partner
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Container para os cards dos sócios --}}
<div class="row g-4" id="partners-list-container">
    @forelse ($partners as $partner)
    {{-- Inclui o card do sócio a partir do arquivo parcial --}}
    @include('partners._partner-card', ['partner' => $partner, 'headerStats' => $headerStats])
    @empty
    <div class="col-12" id="no-partners-alert">
        <div class="alert alert-warning" role="alert">
            No active partners found. Click "Add Partner" to get started.
        </div>
    </div>
    @endforelse
</div>

{{-- TEMPLATE ESCONDIDO PARA O JAVASCRIPT USAR AO ADICIONAR/EDITAR --}}
<template id="partner-card-template">
    <div class="col-xl-4 col-lg-6 col-md-6" id="partner-card-PARTNER_ID_PLACEHOLDER">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="fw-normal mb-0 partner-name">PARTNER_NAME_PLACEHOLDER</h6>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="bx bx-dots-vertical-rounded"></i></button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item edit-partner-btn" href="javascript:void(0);" data-partner=PARTNER_JSON_PLACEHOLDER>Edit / Payout</a>
                            <a class="dropdown-item text-danger delete-partner-btn" href="javascript:void(0);" data-id="PARTNER_ID_PLACEHOLDER" data-item-name="PARTNER_NAME_PLACEHOLDER">Remove</a>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <h3 class="mb-0 me-2 text-primary available-amount">AVAILABLE_AMOUNT_PLACEHOLDER</h3>
                    <small class="text-muted">BRL (Available)</small>
                </div>
                <p class="mb-1"><span class="fw-medium">PIX Key:</span> <span class="pix-key-info">PIX_KEY_PLACEHOLDER (PIX_TYPE_PLACEHOLDER)</span></p>
                <p class="mb-0"><span class="fw-medium">Receiving Percentage:</span> <span class="fw-bold percentage-info">PERCENTAGE_PLACEHOLDER%</span></p>
            </div>
        </div>
    </div>
</template>

{{-- =================================================================== --}}
{{-- MODAIS (COMPLETOS) --}}
{{-- =================================================================== --}}

<div class="modal fade" id="addPartnerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPartnerForm" action="{{ route('partners.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addPartnerName" class="form-label">Partner Name</label>
                        <input type="text" class="form-control" id="addPartnerName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="addPartnerPixKey" class="form-label">PIX Key</label>
                        <input type="text" class="form-control" id="addPartnerPixKey" name="pix_key" required>
                    </div>
                    <div class="mb-3">
                        <label for="addPartnerPixType" class="form-label">PIX Key Type</label>
                        <select class="form-select" id="addPartnerPixType" name="pix_key_type" required>
                            <option selected disabled value="">Select type...</option>
                            <option value="cpf">CPF</option>
                            <option value="cnpj">CNPJ</option>
                            <option value="email">Email</option>
                            <option value="phone">Phone</option>
                            <option value="evp">Random Key (EVP)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addPartnerPercentage" class="form-label">Receiving Percentage (%)</label>
                        <input type="number" class="form-control" id="addPartnerPercentage" name="receiving_percentage" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="addPartnerWithdrawalFrequency" class="form-label">Withdrawal Frequency</label>
                        <select class="form-select" id="addPartnerWithdrawalFrequency" name="withdrawal_frequency" required>
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Partner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editPartnerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPartnerForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editPartnerName" class="form-label">Partner Name</label>
                        <input type="text" class="form-control" id="editPartnerName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPartnerPixKey" class="form-label">PIX Key</label>
                        <input type="text" class="form-control" id="editPartnerPixKey" name="pix_key" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPartnerPixType" class="form-label">PIX Key Type</label>
                        <select class="form-select" id="editPartnerPixType" name="pix_key_type" required>
                            <option value="cpf">CPF</option>
                            <option value="cnpj">CNPJ</option>
                            <option value="email">Email</option>
                            <option value="phone">Phone</option>
                            <option value="evp">Random Key (EVP)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editPartnerPercentage" class="form-label">Receiving Percentage (%)</label>
                        <input type="number" class="form-control" id="editPartnerPercentage" name="receiving_percentage" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="editPartnerWithdrawalFrequency" class="form-label">Withdrawal Frequency</label>
                        <select class="form-select" id="editPartnerWithdrawalFrequency" name="withdrawal_frequency" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection