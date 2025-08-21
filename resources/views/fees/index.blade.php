@extends('layouts/contentNavbarLayout')

@section('title', 'Fees')

@section('page-script')
@vite(['resources/assets/js/ui-toasts.js'])
@vite(['resources/assets/js/account-fee.js'])
@vite(['resources/assets/js/delete-item.js'])
@endsection

@section('content')
<div class="card">
    <h5 class="card-header">Fees
        <small class="text-muted float-end">
            <a href="{{ route('fees.create') }}" class="btn btn-primary">
                <i class="bx bx-plus-circle"></i> &nbsp;&nbsp;
                Add Fee
            </a>
        </small>
    </h5>

    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Type Fee</th>
                    <th>Min Fee</th>
                    <th>Percent Fee</th>
                    <th>Fixed Fee</th>
                    <th>Is Active</th>
                    <th>Assign to User</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($fees as $fee)
                <tr>
                    <td class="">
                        @if ($fee->type == 'IN')
                        <span class="badge bg-label-primary me-1">
                            Pay In
                        </span>
                        @else
                        <span class="badge bg-label-warning me-1">
                            Pay Out
                        </span>
                        @endif
                    </td>

                    <th>BRL {{ number_format($fee->minimum_fee, 2, '.', '') }}</th>
                    <th> {{ number_format($fee->percentage, 2, '.', '') }} %</th>
                    <th>BRL {{ number_format($fee->fixed_fee, 2, '.', '') }}</th>
                    <td>
                        @if ($fee->is_active)
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
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#assignFeeModal" data-fee-id="{{ $fee->id }}">
                            Assign
                        </button>
                    </td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('fees.edit', $fee->id) }}"><i
                                        class="bx bx-edit-alt me-1"></i>
                                    Edit</a>
                                <a class="dropdown-item" href="#"
                                    onclick="deleteItem({{ $fee->id }}, '{{ route('fees.destroy', $fee->id) }}')">
                                    <i class="bx bx-trash me-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach

            </tbody>
        </table>
    </div>
</div>


<div class="modal fade" id="assignFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCenterTitle">Assign Fee to User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignedFeeId">
                <div class="row">
                    <div class="col mb-3">
                        <label for="assignedAccountId" class="form-label">Select User</label>
                        <select id="assignedAccountId" class="form-select">
                            <option value="">Select a Account</option>
                            @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-3">
                        <label for="feeType" class="form-label">Fee Type</label>
                        <select id="feeType" class="form-select">
                            <option value="">Select Fee Type</option>
                            <option value="IN">IN</option>
                            <option value="OUT">OUT</option>
                            <option value="DEFAULT">Default</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                &nbsp;&nbsp;&nbsp;
                <button type="button" class="btn btn-primary" id="saveAccountFee">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection