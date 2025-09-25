@extends('layouts/contentNavbarLayout')

@section('title', 'New Scheduled Take')

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">
        <a href="{{ route('admin.scheduled-takes.index') }}">Scheduled Takes</a> /
    </span> New Schedule
</h4>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Schedule Details</h5>
                <small class="text-muted">Set up a new automatic profit withdrawal for a specific bank.</small>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.scheduled-takes.store') }}" method="POST">
                    @csrf

                    {{-- Bank Selection --}}
                    <div class="mb-3">
                        <label for="bank_id" class="form-label">Bank (Acquirer)</label>
                        <select name="bank_id" id="bank_id" class="form-select @error('bank_id') is-invalid @enderror" required>
                            <option value="" disabled selected>Select a bank to automate...</option>
                            @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('bank_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">The robot will calculate the profit and initiate the payout only for this bank.</div>
                    </div>

                    {{-- Frequency Selection --}}
                    <div class="mb-3">
                        <label for="frequency" class="form-label">Frequency</label>
                        <select name="frequency" id="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                            <option value="" disabled selected>Select frequency...</option>
                            <option value="everyTenMinutes" {{ old('frequency') == 'everyTenMinutes' ? 'selected' : '' }}>Every 10 minutes</option>
                            <option value="everyThirtyMinutes" {{ old('frequency') == 'everyThirtyMinutes' ? 'selected' : '' }}>Every 30 minutes</option>
                            <option value="hourly" {{ old('frequency') == 'hourly' ? 'selected' : '' }}>Hourly</option>
                            <option value="daily" {{ old('frequency') == 'daily' ? 'selected' : '' }}>Daily (at midnight)</option>
                        </select>
                        @error('frequency')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Enable/Disable Switch --}}
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Enable this schedule immediately</label>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                        <a href="{{ route('admin.scheduled-takes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection