@extends('layouts/contentNavbarLayout')

@section('title', 'Banks settings - Banks')

@section('page-script')
{{-- Certifique-se de que delete-item.js está sendo carregado corretamente --}}
@vite(['resources/assets/js/pages-account-settings-account.js', 'resources/assets/js/delete-item.js'])
@endsection

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Account Balances</h1>
        {{-- Pode adicionar um botão de Ação aqui no futuro --}}
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Account ID</th>
                            <th>Acquirer ID</th>
                            <th class="text-end">Available Balance</th>
                            <th class="text-end">Blocked Balance</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($balances as $balance)
                        <tr>
                            <td>
                                <a href="#">{{-- Link para a conta --}}
                                    {{ $balance->account_id }}
                                </a>
                            </td>
                            <td>{{ $balance->acquirer_id ?? 'N/A' }}</td>
                            <td class="text-end fw-bold text-success">
                                R$ {{ number_format($balance->available_balance, 2, ',', '.') }}
                            </td>
                            <td class="text-end fw-bold text-warning">
                                R$ {{ number_format($balance->blocked_balance, 2, ',', '.') }}
                            </td>
                            <td>{{ $balance->updated_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                No accounts with a balance greater than zero found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Links de Paginação --}}
            <div class="mt-3">
                {{ $balances->links() }}
            </div>
        </div>
    </div>
</div>
@endsection