<li class="list-group-item" data-webhook-id="{{ $webhook->id }}">
    <strong>{{ $webhook->event }}</strong> &rarr; <span class="text-muted">{{ $webhook->url }}</span>
    <div class="mt-2">
        <label class="form-label small">Secret Token:</label>
        <div class="input-group">
            <input type="password" id="token-{{ $webhook->id }}" class="form-control form-control-sm" value="{{ $webhook->secret_token }}" readonly>
            <button type="button" class="btn btn-outline-secondary btn-sm toggle-token" data-target-id="token-{{ $webhook->id }}">Show</button>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-2 gap-2">
        {{-- [MODIFICADO] Adicionada classe e data attribute --}}
        <form class="form-regenerate-webhook" action="{{ route('webhooks.regenerate', $webhook) }}" method="POST">
            @csrf @method('PUT')
            <button type="submit" class="btn btn-sm btn-warning">Regenerate Token</button>
        </form>
        {{-- [MODIFICADO] Adicionada classe e data attribute --}}
        <form class="form-delete-webhook" action="{{ route('webhooks.destroy', $webhook) }}" method="POST">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
        </form>
    </div>
</li>