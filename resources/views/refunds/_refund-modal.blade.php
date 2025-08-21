<div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="refund-form">
                <div class="modal-body">
                    <p>Are you sure you want to refund this transaction? This action cannot be undone.</p>
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span>Transaction ID:</span>
                        <strong id="refund-tx-id">--</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Amount to be Refunded:</span>
                        <strong id="refund-amount">--</strong>
                    </div>
                    <div class="alert alert-info mt-3 d-none" id="refund-alert-notice"></div>
                    <hr>
                    <label for="refund-2fa-code" class="form-label">Enter your 2FA Code to confirm:</label>
                    <div class="d-flex justify-content-center">
                        {{-- Inputs para o código 2FA --}}
                        <input type="text" class="form-control text-center mx-1 2fa-input" maxlength="1">
                        <input type="text" class="form-control text-center mx-1 2fa-input" maxlength="1">
                        <input type="text" class="form-control text-center mx-1 2fa-input" maxlength="1">
                        <input type="text" class="form-control text-center mx-1 2fa-input" maxlength="1">
                        <input type="text" class="form-control text-center mx-1 2fa-input" maxlength="1">
                        <input type="text" class="form-control text-center mx-1 2fa-input" maxlength="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>