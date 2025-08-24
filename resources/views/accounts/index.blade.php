@extends('layouts/contentNavbarLayout')

@section('title', 'Account settings - Account')

@section('page-script')
@endsection

@section('content')
<div class="container">
    @can('view', Auth::user())
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Account Management</h5>
        <a href="{{ route('accounts.create') }}" class="btn btn-primary">New Account</a>
    </div>
    @endcan

    <div class="card">
        <div class="card-body border-top">
            <form method="GET" action="{{ route('accounts.index') }}">

                <div class="row g-3 align-items-end justify-content-between">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search User</label>
                        <input type="text" class="form-control" name="search" placeholder="Name " value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-datatable table-responsive pt-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Account name</th>
                        <th>Created At</th>
                        <th>Min Amount IN</th>
                        <th>Max Amount OUT</th>
                        <th>Acquirer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                    <tr>
                        <td>{{ $account->id }}</td>
                        <td>{{ $account->name }}</td>
                        <td>{{ $account->created_at->format('d/m/Y') }}</td>
                        <td>R$ {{ number_format($account->min_amount_transaction, 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($account->max_amount_transaction, 2, ',', '.') }}</td>
                        <td>Bancco 1</td>

                        <td>
                            <a href="{{ route('accounts.edit', $account) }}" class="btn btn-sm btn-info">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">No accounts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{-- Links de paginação --}}
            {{ $accounts->links() }}
        </div>
    </div>
</div>
@endsection