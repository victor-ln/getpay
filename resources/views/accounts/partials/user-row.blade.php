<tr data-user-id="{{ $user->id }}">
    <td>{{ $user->name }}</td>
    <td>{{ $user->email }}</td>
    <td><span class="badge bg-label-primary">{{ ucfirst($user->pivot->role) }}</span></td>
    <td>
        <div class="d-flex">
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-info me-2">Edit</a>
            @if (Auth::user()->isAdmin())
            <form action="{{ route('accounts.users.detach', ['account' => $account->id, 'user' => $user->id]) }}" method="POST" onsubmit="return confirm('Tem certeza?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
            @endif
        </div>
    </td>
</tr>