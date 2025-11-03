@extends('layouts/contentNavbarLayout')

@section('title', 'Payout Approvals')

@section('page-script')
{{-- ✅ Inclui o JS separado via Vite --}}
@vite('resources/assets/js/payout-approvals.js')
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Admin /</span> Payout Approvals
</h4>

<div class="card">
    <h5 class="card-header">Pending Withdrawals</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Account</th>
                    <th>Client (User)</th>
                    <th class="text-end">Amount</th>
                    <th>Destination</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($pendingPayouts as $payment)
                <tr id="payout-row-{{ $payment->id }}">
                    <td>
                        <strong>{{ $payment->created_at->format('Y-m-d') }}</strong>
                        <small class="d-block text-muted">{{ $payment->created_at->format('H:i') }}</small>
                    </td>
                    <td>{{ $payment->account->name ?? 'N/A' }}</td>
                    <td>{{ $payment->user->name ?? 'N/A' }}</td>
                    <td class="text-end">R$ {{ number_format($payment->amount, 2, ',', '.') }}</td>
                    <td>
                        <strong>{{ $payment->name }}</strong>
                        <small class="d-block text-muted">{{ $payment->document ?? 'N/A' }}</small>
                    </td>
                    <td class="text-center">
                        {{-- Botão VIEW --}}
                        <button class="btn btn-sm btn-outline-secondary btn-view-details"
                            data-bs-toggle="modal"
                            data-bs-target="#viewDetailsModal"
                            data-url="{{ route('admin.payout-approvals.details', $payment) }}">
                            View
                        </button>

                        {{-- Botão CANCEL --}}
                        <button class="btn btn-sm btn-outline-danger btn-cancel-payout"
                            data-url="{{ route('admin.payout-approvals.cancel', $payment) }}">
                            Cancel
                        </button>

                        {{-- Botão APPROVE --}}
                        <button class="btn btn-sm btn-success btn-approve-payout"
                            data-bs-toggle="modal"
                            data-bs-target="#approveModal"
                            data-url="{{ route('admin.payout-approvals.approve', $payment) }}">
                            Approve
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center p-5">
                        <h5 class="mb-0">No pending payouts to approve.</h5>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pendingPayouts->hasPages())
    <div class="card-footer d-flex justify-content-center">
        {{ $pendingPayouts->links() }}
    </div>
    @endif
</div>


{{-- ================================================= --}}
{{-- MODAL 1: VIEW DETAILS                         --}}
{{-- ================================================= --}}
<div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewDetailsModalLabel">Payout Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Estado de Carregamento --}}
                <div id="view-modal-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
                {{-- Conteúdo --}}
                <div id="view-modal-content" class="d-none">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Amount:</span> <strong id="detail-amount">--</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Fee:</span> <strong id="detail-fee">--</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><strong>Total Debit:</strong> <strong class="fs-5" id="detail-total-debit">--</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>To:</span> <strong id="detail-dest-name">--</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Document:</span> <strong id="detail-dest-doc">--</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>PIX Key:</span> <strong id="detail-dest-key">--</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Requested by:</span> <strong id="detail-account">--</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Date:</span> <strong id="detail-date">--</strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ================================================= --}}
{{-- MODAL 2: APPROVE WITH PIN                     --}}
{{-- ================================================= --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">

            {{-- ETAPA 1: Inserir o PIN --}}
            <form id="approve-modal-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Enter Security PIN</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <label for="pin-input-1" class="form-label">Enter your 6-digit PIN to approve this payout.</label>
                    <div class="d-flex justify-content-center gap-2" id="pin-inputs">
                        <input type="password" class="form-control text-center fs-3 p-1" id="pin-input-1" maxlength="1" style="width: 40px;" pattern="[0-9]*" inputmode="numeric">
                        <input type="password" class="form-control text-center fs-3 p-1" id="pin-input-2" maxlength="1" style="width: 40px;" pattern="[0-9]*" inputmode="numeric">
                        <input type="password" class="form-control text-center fs-3 p-1" id="pin-input-3" maxlength="1" style="width: 40px;" pattern="[0-9]*" inputmode="numeric">
                        <input type="password" class="form-control text-center fs-3 p-1" id="pin-input-4" maxlength="1" style="width: 40px;" pattern="[0-9]*" inputmode="numeric">
                        <input type="password" class="form-control text-center fs-3 p-1" id="pin-input-5" maxlength="1" style="width: 40px;" pattern="[0-9]*" inputmode="numeric">
                        <input type="password" class="form-control text-center fs-3 p-1" id="pin-input-6" maxlength="1" style="width: 40px;" pattern="[0-9]*" inputmode="numeric">
                    </div>
                    <div id="pin-error-msg" class="text-danger small mt-2 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Approval</button>
                </div>
            </form>

            {{-- ETAPA 2: Processando... (escondido) --}}
            <div id="approve-modal-processing" class="modal-body text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Processing...</span>
                </div>
                <h5 class="mt-3 mb-0">Processing Approval...</h5>
                <small class="text-muted">Please wait.</small>
            </div>

        </div>
    </div>
</div>
@endsection