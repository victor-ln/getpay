@extends('layouts/contentNavbarLayout')

@section('title', 'GetPay - Create Batch')

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">Payments /</span> Payments 
</h4>

<div class="row">
  <div class="col-xl-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Payments </h5>
        <a href="{{ route('batches.create') }}" class="btn btn-primary">Create New Batch</a>
      </div>
      <div class="card-body">
        <!-- Table or content to list all payment batches would go here -->
        
    @if(!$batches->isNotEmpty())
    <p>List of all payment batches will be displayed here.</p>
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Status</th>
              <th>Created At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($batches as $batch)
              <tr>
                <td>{{ $batch->id }}</td>
                <td>{{ $batch->status }}</td>
                <td>{{ $batch->created_at }}</td>
                <td>
                  <a href="{{ route('batches.show', $batch) }}" class="btn btn-sm btn-primary">View</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
    @else
        <p class="text-center mb-4">No payment batches found.</p>
    @endif
      </div>
    </div>
  </div>
</div>
@endsection