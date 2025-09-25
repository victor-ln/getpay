@extends('layouts/contentNavbarLayout')

@section('title', 'Scheduled Takes Automation')

@vite('resources/assets/js/schedule-toggle-status.js')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold py-3 mb-0">
        <span class="text-muted fw-light">Admin /</span> Scheduled Takes
    </h4>
    <a href="{{ route('admin.scheduled-takes.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i> New Schedule
    </a>
</div>

{{-- Bloco para exibir as mensagens de sucesso/erro --}}
@if (session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="card">
    <h5 class="card-header">Active and Inactive Schedules</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Bank (Acquirer)</th>
                    <th>Frequency</th>
                    <th class="text-center">Status</th>
                    <th>Created On</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($scheduledTakes as $task)
                <tr>
                    <td><strong>{{ $task->bank->name ?? 'Bank not found' }}</strong></td>
                    <td>
                        <span class="badge bg-label-info">{{ $task->frequency }}</span>
                    </td>
                    <td class="text-center">
                        {{-- ✅ [CORRIGIDO] O interruptor (toggle) interativo --}}
                        <div class="form-check form-switch d-inline-block">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="toggle-{{ $task->id }}"
                                data-url="{{ route('admin.scheduled-takes.toggle', $task) }}"
                                @if($task->is_active) checked @endif
                            >
                        </div>
                    </td>
                    <td>{{ $task->created_at->format('Y-m-d') }}</td>
                    <td class="text-end">
                        <form action="{{ route('admin.scheduled-takes.destroy', $task) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this schedule?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center p-5">
                        No scheduled Takes found. <a href="{{ route('admin.scheduled-takes.create') }}">Click here to create the first one.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection