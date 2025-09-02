@extends('layouts/contentNavbarLayout')

@section('title', 'Pix Key for take')

@section('page-script')
{{-- Certifique-se de que delete-item.js está sendo carregado corretamente --}}
@vite(['resources/assets/js/pages-account-settings-account.js', 'resources/assets/js/delete-item.js'])
@endsection
@section('content')
    <h5>Add New PIX Key</h5>

    <form action="{{ route('admin.payout-destinations.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="nickname">Nickname (for your identification)</label>
            <input type="text" name="nickname" id="nickname" class="form-control" value="{{ old('nickname') }}" required>
        </div>

        <div class="form-group">
            <label for="owner_name">Owner's Full Name</label>
            <input type="text" name="owner_name" id="owner_name" class="form-control" value="{{ old('owner_name') }}" required>
        </div>

        <div class="form-group">
            <label for="owner_document">Owner's Document (CPF/CNPJ)</label>
            <input type="text" name="owner_document" id="owner_document" class="form-control" value="{{ old('owner_document') }}" required>
        </div>

        <div class="form-group">
            <label for="pix_key_type">Key Type</label>
            <select name="pix_key_type" id="pix_key_type" class="form-control" required>
                <option value="">Select...</option>
                <option value="CNPJ">CNPJ</option>
                <option value="CPF">CPF</option>
                <option value="Email">Email</option>
                <option value="Phone">Phone</option>
                <option value="Random">Random Key</option>
            </select>
        </div>

        <div class="form-group">
            <label for="pix_key">PIX Key</label>
            <input type="text" name="pix_key" id="pix_key" class="form-control" value="{{ old('pix_key') }}" required>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save PIX Key</button>
    </form>
@endsection