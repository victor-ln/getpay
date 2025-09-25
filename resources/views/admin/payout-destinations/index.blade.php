@extends('layouts/contentNavbarLayout')



@section('title', 'Add New PIX Key Destination')


@section('page-script')
{{-- Certifique-se de que delete-item.js está sendo carregado corretamente --}}
@vite(['resources/assets/js/pages-account-settings-account.js', 'resources/assets/js/delete-item.js'])
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5>PIX Key Destinations</h5>
    <a href="{{ route('admin.payout-destinations.create') }}" class="btn btn-primary">Add New Key</a>
</div>

@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nickname</th>
                    <th>Type</th>
                    <th>PIX Key</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Default For Takes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($destinations as $destination)
                <tr>
                    <td>{{ $destination->nickname }}</td>
                    <td>{{ $destination->pix_key_type }}</td>
                    <td>{{ $destination->pix_key }}</td>
                    <td>{{ $destination->owner_name }}</td>
                    <td>
                        @if ($destination->is_active)
                        <span class="badge bg-success">Active</span>
                        @else
                        <span class="badge bg-danger">Inactive</span>
                        @endif
                    </td>
                    <td class="text-center">
                        {{-- ✅ NOVO INDICADOR DE STATUS --}}
                        @if($destination->is_default_for_takeouts)
                        <span class="badge bg-label-primary"><i class="bx bx-check-circle me-1"></i> Default</span>
                        @else
                        <span class="text-muted ">No</span>
                        @endif
                    </td>
                    <td>
                        @if(!$destination->is_default_for_takeouts)
                        {{-- O botão só aparece se este NÃO for o padrão atual --}}
                        <form action="{{ route('admin.payout-destinations.setDefaultTake', $destination) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                Set as Default
                            </button>
                        </form>
                        @endif
                        <a href="#" class="btn btn-sm btn-info">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center">No PIX key destinations found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection