<tr data-partner-id="{{ $partner->id }}">
    <td>{{ $partner->name }}</td>
    <td class="text-end">{{ number_format($partner->pivot->commission_rate * 100, 2, ',', '.') }}%</td>
    <td class="text-end">{{ number_format($partner->pivot->platform_withdrawal_fee_rate * 100, 2, ',', '.') }}%</td>
    <td class="text-center">
        {{-- [MODIFICADO] Adicionada classe ao formulário de remoção --}}
        <form class="form-detach-partner" action="{{ route('accounts.partners.detach', ['account' => $account, 'partner' => $partner]) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
        </form>
    </td>
</tr>