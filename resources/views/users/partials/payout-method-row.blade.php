<li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $payoutMethod->id }}">
    <div>
        <span><strong>{{ $payoutMethod->pix_key_type }}:</strong> {{ $payoutMethod->pix_key }}</span>
        @if($payoutMethod->is_default)
        <span class="badge bg-success ms-2">Default</span>
        @endif
    </div>
    <div class="d-flex gap-2">
        @unless($payoutMethod->is_default)
        <form class="form-set-default" action="{{ route('partner-payout-methods.setDefault', $payoutMethod) }}" method="POST">
            @csrf @method('PUT')
            <button type="submit" class="btn btn-sm btn-outline-secondary">Set as Default</button>
        </form>
        @endunless
        <form class="form-delete-payout" action="{{ route('partner-payout-methods.destroy', $payoutMethod) }}" method="POST">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
        </form>
    </div>
</li>