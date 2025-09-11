@extends('layouts/contentNavbarLayout')

@section('title', 'Pix Key for take')

@section('page-script')
{{-- Certifique-se de que delete-item.js está sendo carregado corretamente --}}
@vite(['resources/assets/js/pages-account-settings-account.js', 'resources/assets/js/delete-item.js'])
@endsection
@section('content')
<h5>Add New PIX Key</h5>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.payout-destinations.store') }}" method="POST">
            @csrf

            <!-- Identificação Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-muted mb-3">
                        <i class="bx bx-tag-alt me-1"></i>
                        Identification
                    </h6>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" name="nickname" id="nickname" class="form-control"
                            value="{{ old('nickname') }}" placeholder="Nickname" required>
                        <label for="nickname">
                            <i class="bx bx-bookmark me-1"></i>
                            Nickname (for your identification)
                        </label>
                    </div>
                </div>
            </div>

            <!-- Owner Information Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-muted mb-3">
                        <i class="bx bx-user me-1"></i>
                        Owner Information
                    </h6>
                </div>
                <div class="col-md-7">
                    <div class="form-floating">
                        <input type="text" name="owner_name" id="owner_name" class="form-control"
                            value="{{ old('owner_name') }}" placeholder="Full Name" required>
                        <label for="owner_name">
                            <i class="bx bx-user-circle me-1"></i>
                            Owner's Full Name
                        </label>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-floating">
                        <input type="text" name="owner_document" id="owner_document" class="form-control"
                            value="{{ old('owner_document') }}" placeholder="Document" required>
                        <label for="owner_document">
                            <i class="bx bx-id-card me-1"></i>
                            Document (CPF/CNPJ)
                        </label>
                    </div>
                </div>
            </div>

            <!-- PIX Information Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-muted mb-3">
                        <i class="bx bx-qr me-1"></i>
                        PIX Key Information
                    </h6>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <select name="pix_key_type" id="pix_key_type" class="form-select" required>
                            <option value="">Select...</option>
                            <option value="CNPJ" {{ old('pix_key_type') == 'CNPJ' ? 'selected' : '' }}>CNPJ</option>
                            <option value="CPF" {{ old('pix_key_type') == 'CPF' ? 'selected' : '' }}>CPF</option>
                            <option value="Email" {{ old('pix_key_type') == 'Email' ? 'selected' : '' }}>Email</option>
                            <option value="Phone" {{ old('pix_key_type') == 'Phone' ? 'selected' : '' }}>Phone</option>
                            <option value="EVP" {{ old('pix_key_type') == 'EVP' ? 'selected' : '' }}>Random Key</option>
                        </select>
                        <label for="pix_key_type">
                            <i class="bx bx-category me-1"></i>
                            Key Type
                        </label>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-floating">
                        <input type="text" name="pix_key" id="pix_key" class="form-control"
                            value="{{ old('pix_key') }}" placeholder="PIX Key" required>
                        <label for="pix_key">
                            <i class="bx bx-key me-1"></i>
                            PIX Key
                        </label>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <hr>
                    <div class="d-flex gap-3 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>
                            Save PIX Key
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection