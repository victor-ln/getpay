@extends('layouts/contentNavbarLayout')

@section('title', 'Products - ' . $selectedAccount->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-3 mb-0">
        <span class="text-muted fw-light">Admin /</span> Products
    </h4>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> Add New Product
    </a>
</div>

{{-- Mostra qual a conta que está a ser visualizada --}}
<div class="alert alert-info" role="alert">
    <i class="bx bx-info-circle me-1"></i> Showing products for account: <strong>{{ $selectedAccount->name }}</strong>
</div>

<div class="card">
    <h5 class="card-header">Product List</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="text-end">Price</th>
                    <th class="text-center">Status</th>
                    <th>Created At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($products as $product)
                <tr>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td class="text-end">R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                    <td class="text-center">
                        @if($product->status == 'active')
                        <span class="badge bg-label-success">Active</span>
                        @else
                        <span class="badge bg-label-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $product->created_at->format('d/m/Y') }}</td>
                    <td class="text-end">
                        <div class="d-inline-block">
                            {{-- Botão Editar --}}
                            <a class="btn btn-sm btn-info"
                                href="{{ route('admin.products.edit', $product->id) }}">
                                Edit
                            </a>

                            {{-- Botão Apagar (com formulário) --}}
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Are you sure you want to delete this product?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center p-5">
                        <h5 class="mb-0">No products found for this account.</h5>
                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm mt-3">Create the first one</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($products->hasPages())
    <div class="card-footer d-flex justify-content-center">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection