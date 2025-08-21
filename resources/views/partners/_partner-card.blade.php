{{-- resources/views/partners/_partner-card.blade.php --}}

<div class="col-xl-4 col-lg-6 col-md-6" id="partner-card-{{ $partner->id }}">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <h6 class="fw-normal mb-0 partner-name">{{ $partner->name }}</h6>
                <div class="dropdown">
                    <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item edit-partner-btn" href="javascript:void(0);" data-partner='{{ json_encode($partner) }}'>Edit / Payout</a>
                        <a class="dropdown-item text-danger delete-partner-btn" href="javascript:void(0);" data-id="{{ $partner->id }}" data-item-name="{{ $partner->name }}">Remove</a>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center mb-3">
                @php
                $netForDistribution = $headerStats['netForDistribution'] ?? 0;
                $availableAmount = $netForDistribution * ($partner->receiving_percentage / 100);
                @endphp
                <h3 class="mb-0 me-2 text-primary available-amount">{{ number_format($availableAmount, 2, ',', '.') }}</h3>
                <small class="text-muted">BRL (Available)</small>
            </div>
            <p class="mb-1"><span class="fw-medium">PIX Key:</span> <span class="pix-key-info">{{ $partner->pix_key }} ({{ Str::upper($partner->pix_key_type) }})</span></p>
            <p class="mb-0"><span class="fw-medium">Receiving Percentage:</span> <span class="fw-bold percentage-info">{{ number_format($partner->receiving_percentage, 2) }}%</span></p>
        </div>
    </div>
</div>