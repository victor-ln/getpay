@extends('layouts/contentNavbarLayout')

@section('title', 'Fees')

@section('page-script')
@vite(['resources/assets/js/ui-toasts.js'])
@vite(['resources/assets/js/mask.js'])
@endsection

@section('content')
<div class="row">
    <div class="nav-align-top">
        <ul class="nav nav-pills flex-column flex-md-row mb-6">
            <li class="nav-item"><a class="nav-link active" href="javascript:void(0);"><i
                        class="bx bx-sm bx-dollar me-1_5"></i> Fees</a></li>
            <li class="nav-item"><a class="nav-link " href="{{ route('fees.index') }}"><i
                        class="bx bx-sm bx-arrow-back me-1_5"></i> Back</a></li>
        </ul>
    </div>
    <div class="col-xl">
        <div class="card mb-6">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Fees</h5>
            </div>
            <div class="card-body">
                {!! Form::model($fee ?? new App\Models\Fee(), ['route' => $fee ? ['fees.update', $fee->id] :
                'fees.store', 'method' => $fee ? 'PUT' : 'POST']) !!}
                <div class="row">
                    <div class="mb-6 col-md-4">
                        {!! Form::label('fixed_fee', 'Fixed Fee') !!}
                        {!! Form::text('fixed_fee', null, ['class' => 'form-control money', 'placeholder' => '0,00'])
                        !!}
                        @error('fixed_fee')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-6 col-md-4">
                        {!! Form::label('minimum_fee', 'Base Fee') !!}
                        {!! Form::text('minimum_fee', null, ['class' => 'form-control money', 'placeholder' => '0,00'])
                        !!}
                        @error('minimum_fee')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-6 col-md-4">
                        {!! Form::label('percentage', 'Percent Fee') !!}
                        {!! Form::text('percentage', null, ['class' => 'form-control ', 'placeholder' => '15%'])
                        !!}
                        @error('percentage')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-6 col-md-3 d-none">
                        {!! Form::label('type', 'Type Fee') !!}
                        {!! Form::select('type', ['IN' => 'IN', 'OUT' => 'OUT'], null, ['class' => 'form-control'])
                        !!}
                        @error('type')
                        <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save</button>
                {!! Form::close() !!}
            </div>
        </div>
    </div>

</div>
@endsection