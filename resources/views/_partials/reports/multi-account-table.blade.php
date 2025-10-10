<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Document</th>
                <th class="text-center">Associated Accounts</th>
                <th class="text-end">Total Volume</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
            <tr>
                <td>
                    <strong>{{ $item->document }}</strong>
                </td>
                <td class="text-center">
                    <span class="badge bg-label-warning">{{ $item->associated_accounts_count }} accounts</span>
                </td>
                <td class="text-end">
                    <strong>{{ number_format($item->total_volume, 2) }}</strong>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary view-user-detail"
                        data-document="{{ $item->document }}"
                        title="View Details">
                        <i class="bx bx-show"></i> View
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    <i class="bx bx-info-circle bx-lg"></i>
                    <p class="mt-2 mb-0">No users with multiple accounts found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($data->hasPages())
    <div class="mt-3">
        {{ $data->links() }}
    </div>
    @endif
</div>