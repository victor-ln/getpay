@extends('layouts/contentNavbarLayout')

@section('title', 'Central de Relatórios')

@section('content')
<h5 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Dashboard /</span> Reporting Center
</h5>

{{-- Bloco para exibir as mensagens de sucesso/erro --}}
@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card">
    <h5 class="card-header">My Requested Reports</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Request Date</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($reports as $report)
                <tr>
                    <td><i class="bx bx-file me-2"></i><strong>{{ $report->file_name }}</strong></td>
                    <td>{{ $report->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="text-center">
                        @if ($report->status === 'completed')
                        <span class="badge bg-label-success">Completed</span>
                        @elseif ($report->status === 'processing')
                        <span class="badge bg-label-info">Processing...</span>
                        @elseif ($report->status === 'pending')
                        <span class="badge bg-label-secondary">Pending</span>
                        @elseif ($report->status === 'failed')
                        <span class="badge bg-label-danger" title="{{ $report->failure_reason }}">Failed</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if ($report->status === 'completed')
                        <a href="{{ route('reports.download', $report) }}" class="btn btn-sm btn-primary">
                            <i class="bx bx-download me-1"></i> Download
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center p-5">
                        You have not requested any reports yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-center">
        {{ $reports->links() }}
    </div>
</div>
@endsection