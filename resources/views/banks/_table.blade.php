<div class="table-responsive text-nowrap">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Name</th>
                <th>Base URL</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody class="table-border-bottom-0">
            @forelse ($banks as $bank)
            <tr>
                <td><strong>{{ $bank->name }}</strong></td>
                <td>{{ $bank->baseurl }}</td>
                <td>
                    <span class="badge bg-label-{{ $bank->active ? 'success' : 'danger' }} me-1">
                        {{ $bank->active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="text-end">
                    <div class="d-inline-block">
                        <button class="btn btn-sm btn-info btn-show-details"
                            data-bs-toggle="modal"
                            data-bs-target="#bankDetailsModal"
                            data-bank-id="{{ $bank->id }}">
                            Details
                        </button>
                        <a class="btn btn-sm btn-secondary" href="{{ route('banks.edit', $bank->id) }}">
                            Edit
                        </a>
                        {{-- O seu código de delete continua aqui --}}
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center p-4">No banks found in this category.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>