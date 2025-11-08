@extends('layouts/contentNavbarLayout')

@section('title', 'GetPay - Create Batch')

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Payments /</span> Create New Batch
</h4>

<div class="row">
  <div class="col-xl-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">New Payment Batch</h5>
      </div>
      <div class="card-body">

        @if (session('success'))
        <div class="alert alert-success" role="alert">
          {{ session('success') }}
        </div>
        @endif
        @if (session('error'))
        <div class="alert alert-danger" role="alert">
          {{ session('error') }}
        </div>
        @endif
        @if ($errors->any())
        <div class="alert alert-danger">
            <h6 class="alert-heading mb-1">Whoops! Something went wrong.</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('batches.store') }}">
            @csrf

            <h5 class="mb-3">Batch Details</h5>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label" for="acquirer_id">Acquirer (Liquidante)</label>
                <select name="acquirer_id" id="acquirer_id" class="form-select" required>
                  <option value="">Select...</option>
                  @foreach ($acquirers as $acquirer)
                  <option value="{{ $acquirer->id }}" {{ old('acquirer_id') == $acquirer->id ? 'selected' : '' }}>
                    {{ $acquirer->name }}
                  </option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label" for="total_amount">Total Amount</label>
                <input type="number" name="total_amount" id="total_amount"
                       class="form-control"
                       placeholder="80000.00"
                       step="0.01" min="0.01"
                       value="{{ old('total_amount') }}" required>
              </div>

              <div class="col-md-4 mb-3">
                <label class="form-label" for="number_of_splits">Number of Splits</label>
                <input type="number" name="number_of_splits" id="number_of_splits"
                       class="form-control"
                       placeholder="1"
                       min="1" step="1"
                       value="{{ old('number_of_splits', 1) }}" required>
              </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Payer Details</h5>
            <p class="text-muted small">This information will be replicated for every split payment.</p>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="name">Payer Full Name</label>
                <input type="text" name="name" id="name"
                       class="form-control"
                       placeholder="John Doe"
                       value="{{ old('name') }}" required>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label" for="document">Payer Document (CPF/CNPJ)</label>
                <input type="text" name="document" id="document"
                       class="form-control"
                       placeholder="123.456.789-00"
                       value="{{ old('document') }}" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="description">Description (Optional)</label>
              <input type="text" name="description" id="description"
                     class="form-control"
                     placeholder="Payment for services..."
                     value="{{ old('description') }}">
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary">Create Batch</button>
            </div>
        </form>
        </div>
    </div>
  </div>
</div>
@endsection