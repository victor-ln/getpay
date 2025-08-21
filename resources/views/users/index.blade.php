@extends('layouts/contentNavbarLayout')

@section('title', 'Account settings - Account')

@section('page-script')
@vite(['resources/assets/js/pages-account-settings-account.js'])
@endsection

@section('content')
<div class="card">

    <h5 class="card-header">Users
        @if (Auth::user()->level == 'admin')
        <small class="text-muted float-end">
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="bx bx-plus-circle"></i> &nbsp;&nbsp;
                Add User
            </a>
        </small>
        @endif
    </h5>

    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>E-mail</th>
                    <th>Role</th>
                    <th>status</th>
                    <th>Account</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge bg-label-info me-1">{{ $user->level }}</span>
                    </td>
                    <td>
                        @if ($user->status)
                        <span class="badge bg-label-primary me-1">
                            Active
                        </span>
                        @else
                        <span class="badge bg-label-warning me-1">
                            Inactive
                        </span>
                        @endif
                    </td>
                    <td>
                        @forelse ($user->accounts as $account)
                        <span class="badge bg-label-secondary">{{ $account->name }}</span>
                        @empty
                        <span class="text-muted ">---</span>
                        @endforelse
                    </td>

                    <td>
                        <div class="dropdown">
                            <a class="btn btn-primary btn-sm" href="{{ route('users.edit', $user->id) }}"><i
                                    class="bx bx-edit-alt me-1"></i>
                                Edit</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Paginação --}}
    <div class="card-footer">
        {{ $users->links() }}
    </div>
</div>
@endsection