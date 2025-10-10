<div class="table-responsive text-nowrap">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Dispute Date</th>
                <th>Transaction ID</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
                <th>Details</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody class="table-border-bottom-0">
            @forelse (($med ?? []) as $medItem)
            <tr>
                <td>
                    {{ $medItem['dataMed'] ? \Carbon\Carbon::parse($medItem['dataMed'])->format('d/m/Y H:i') : 'N/A' }}
                </td>
                <td>
                    <a href="#">{{ $medItem['externalId'] ?? 'N/A' }}</a>
                </td>
                <td class="text-end">
                    R$ {{ number_format($medItem['amount'] ?? 0, 2, ',', '.') }}
                </td>
                <td>
                    @php
                    $statusClass = match($medItem['status'] ?? 'N/A') {
                    'OPEN' => 'bg-label-warning',
                    'CLOSED' => 'bg-label-success',
                    'REJECTED' => 'bg-label-danger',
                    default => 'bg-label-secondary'
                    };
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $medItem['status'] ?? 'N/A' }}</span>
                </td>
                <td>
                    {{ \Illuminate\Support\Str::limit($medItem['details'] ?? 'N/A', 50) }}
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary">View Details</button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center p-5">
                    No MED disputes found for this acquirer.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <div>
            <small class="text-muted">
                Showing {{ ($pagination['count'] ?? 0) }} of {{ $total ?? 0 }} disputes
            </small>
        </div>

        @php
        $currentPage = $pagination['page'] ?? 1;
        $perPage = $pagination['perPage'] ?? 20;
        $totalPages = ceil(($total ?? 0) / $perPage);
        @endphp

        @if($totalPages > 1)
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
                {{-- Botão Anterior --}}
                <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                    <button class="page-link med-page-btn"
                        data-page="{{ $currentPage - 1 }}"
                        {{ $currentPage <= 1 ? 'disabled' : '' }}>
                        <i class="tf-icon bx bx-chevron-left"></i>
                    </button>
                </li>

                {{-- Primeira página --}}
                @if($currentPage > 3)
                <li class="page-item">
                    <button class="page-link med-page-btn" data-page="1">1</button>
                </li>
                @if($currentPage > 4)
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
                @endif
                @endif

                {{-- Páginas ao redor da atual --}}
                @for($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++)
                    <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                    <button class="page-link med-page-btn" data-page="{{ $i }}">{{ $i }}</button>
                    </li>
                    @endfor

                    {{-- Última página --}}
                    @if($currentPage < $totalPages - 2)
                        @if($currentPage < $totalPages - 3)
                        <li class="page-item disabled">
                        <span class="page-link">...</span>
                        </li>
                        @endif
                        <li class="page-item">
                            <button class="page-link med-page-btn" data-page="{{ $totalPages }}">{{ $totalPages }}</button>
                        </li>
                        @endif

                        {{-- Botão Próximo --}}
                        <li class="page-item {{ $currentPage >= $totalPages ? 'disabled' : '' }}">
                            <button class="page-link med-page-btn"
                                data-page="{{ $currentPage + 1 }}"
                                {{ $currentPage >= $totalPages ? 'disabled' : '' }}>
                                <i class="tf-icon bx bx-chevron-right"></i>
                            </button>
                        </li>
            </ul>
        </nav>
        @else
        <div>
            <small class="text-muted">Page {{ $currentPage }} of {{ $totalPages }}</small>
        </div>
        @endif
    </div>
</div>