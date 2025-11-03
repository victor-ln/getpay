@extends('layouts/contentNavbarLayout')

@section('title', 'Edit Product')

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">
        <a href="{{ route('admin.products.index') }}">Products</a> /
    </span> Edit Product
</h4>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <h5 class="card-header">Edit Product: {{ $product->name }}</h5>
            <div class="card-body">
                <form action="{{ route('admin.products.update', $product->id) }}" method="POST">
                    @csrf
                    @method('PUT') {{-- Importante para o método update --}}

                    {{-- O formulário está num ficheiro parcial para ser reutilizado --}}
                    @include('admin.products._form', ['product' => $product])

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary me-2">Update Product</button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection