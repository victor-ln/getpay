@extends('layouts/contentNavbarLayout')

@section('title', 'GetPay - Batch Details')

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
      var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
      });
    });
  </script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Payments / Batches /</span> Batch #{{ $batch->id }}
</h4>

<div class="card mb-4">
  <div class="card-body">
    <div class="row">
      <div class="col-md-3">
        <h5 class="mb-0">Batch Summary</h5>
      </div>
      <div class="col-md-3">
        <small class="text-muted">Total Amount</small>
        <p class="fw-bold mb-0">R$ {{ number_format($batch->total_amount , 2, ',', '.') }}</p>
      </div>
      <div class="col-md-3">
        <small class="text-muted">Splits</small>
        <p class="fw-bold mb-0">{{ $batch->number_of_splits }}</p>
      </div>
      <div class="col-md-3">
        <small class="text-muted">Created By</small>
        <p class="fw-bold mb-0">{{ $batch->user->name ?? 'N/A' }}</p>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <h5 class="card-header">Split Payments</h5>
  <div class="table-responsive text-nowrap">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>ID</th>
          <th>Status</th>
          <th>Amount</th>
          <th>Acquirer TX ID</th>
          <th>Payment</th>
        </tr>
      </thead>
      <tbody class="table-border-bottom-0">
        @forelse ($batch->transactions as $payment)
        <tr>
          <td><strong>{{ $payment->id }}</strong></td>
          
          <td>
            @if ($payment->status == 'pending')
              <span class="badge bg-label-warning me-1">
                <i class="bx bx-time-five bx-xs me-1"></i> Pending
              </span>
            @elseif ($payment->status == 'processed')
              <span class="badge bg-label-info me-1">
                <i class="bx bx-qr-scan bx-xs me-1"></i> Awaiting Payment
              </span>
            @elseif ($payment->status == 'paid' || $payment->status == 'completed')
              <span class="badge bg-label-success me-1">
                <i class="bx bx-check-double bx-xs me-1"></i> Paid
              </span>
            @elseif ($payment->status == 'failed')
              <span class="badge bg-label-danger me-1">
                <i class="bx bx-x-circle bx-xs me-1"></i> Failed
              </span>
            @else
              <span class="badge bg-label-secondary me-1">{{ $payment->status }}</span>
            @endif
          </td>

          <td>R$ {{ number_format($payment->amount , 2, ',', '.') }}</td>
          
          <td>{{ $payment->provider_transaction_id ?? '---' }}</td>

          <td>
            @if ($payment->qr_code_image && $payment->copy_paste_code)
              <button type="button" class="btn btn-sm btn-icon btn-primary" 
                      data-bs-toggle="modal" data-bs-target="#qrModal-{{ $payment->id }}">
                <span class="tf-icons bx bx-qr"></span>
              </button>
              
              <button type="button" class="btn btn-sm btn-icon btn-secondary" 
                      data-bs-toggle="popover" data-bs-placement="top"
                      title="Pix Copia e Cola"
                      data-bs-content="{{ $payment->copy_paste_code }}">
                <span class="tf-icons bx bx-copy"></span>
              </button>

            @elseif ($payment->status == 'pending')
              (Processing...)
            @elseif ($payment->status == 'failed')
              (Error)
            @else
              (N/A)
            @endif
          </td>
        </tr>

        <div class="modal fade" id="qrModal-{{ $payment->id }}" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">R$ {{ number_format($payment->amount, 2, ',', '.') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
                <div class="modal-body text-center">
                    <p>Scan the code below to pay:</p>
                    <img src="{{ $payment->qr_code_image }}" 
                        alt="PIX QR Code" 
                        style="max-width: 100%; height: auto;">
                    <hr>
                    <p class="text-muted small">Payment for ID: {{ $payment->id }}</p>
                </p>
                </div>
            </div>
          </div>
        </div>

        @empty
        <tr>
          <td colspan="5" class="text-center">No payments found for this batch.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection