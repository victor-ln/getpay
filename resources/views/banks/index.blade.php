@extends('layouts/contentNavbarLayout')

@section('title', 'Banks settings - Banks')

@section('page-script')
{{-- Certifique-se de que delete-item.js está sendo carregado corretamente --}}
@vite(['resources/assets/js/pages-account-settings-account.js', 'resources/assets/js/delete-item.js'])
@endsection

@section('content')
<div class="card">

    <h5 class="card-header">Banks/Acquirers
        @if (Auth::user()->level == 'admin')
        <small class="text-muted float-end">
            <a href="{{ route('banks.create') }}" class="btn btn-primary">
                <i class="bx bx-plus-circle"></i> &nbsp;&nbsp;
                Add Bank
            </a>
        </small>
        @endif
    </h5>

    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Base URL</th>
                    <th>status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($banks as $bank)
                <tr>
                    <td>{{ $bank->name }}</td>
                    <td>{{ $bank->baseurl }}</td>
                    <td>
                        <span class="badge bg-label-{{ $bank->active ? 'success' : 'danger' }} me-1">
                            {{ $bank->active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('banks.edit', $bank->id) }}">
                                    <i class="bx bx-edit-alt me-1"></i> Edit
                                </a>


                                <a class="dropdown-item" href="#"
                                    onclick="deleteItem({{ $bank->id }}, '{{ route('banks.destroy', $bank->id) }}')">
                                    <i class="bx bx-trash me-1"></i> Delete
                                </a>

                                @if (!$bank->active)
                                <form action="{{ route('banks.activate', $bank->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="dropdown-item">
                                        <i class="bx bx-check-circle me-1"></i> Activate
                                    </button>
                                </form>
                                @endif

                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection