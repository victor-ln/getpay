@extends('layouts/contentNavbarLayout')


@section('title', 'Account Settings - ' . ($account->name ?? 'New Account'))

@section('page-script')

@vite(['resources/assets/js/pages-account-settings-account.js', 'resources/assets/js/secret.js', 'resources/assets/js/copy.js', 'resources/assets/js/pix-key-manager.js', 'resources/assets/js/webhook-page.js', 'resources/assets/js/account-user-manager.js', 'resources/assets/js/account-partner-manager.js'])
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Account /</span> {{ isset($account) ? 'Edit Account' : 'New Account' }}
</h4>

<div class="row">
    <div class="col-md-12">

        {{-- NAVEGAÇÃO POR ABAS --}}
        <ul class="nav nav-pills flex-column flex-md-row mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="#account-details" data-bs-toggle="tab"><i class="bx bx-buildings me-1"></i> Account</a>
            </li>
            {{-- As abas de detalhes só aparecem no modo de edição --}}
            @isset($account)
            <li class="nav-item">
                <a class="nav-link" href="#users" data-bs-toggle="tab"><i class="bx bx-user me-1"></i> Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#fees" data-bs-toggle="tab"><i class="bx bx-dollar-circle me-1"></i> Fee History</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#pix-keys" data-bs-toggle="tab"><i class="bx bxs-key me-1"></i> PIX KEY</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#developer" data-bs-toggle="tab"><i class="bx bx-code-block me-1"></i> Developers Settings</a>
            </li>

            @if (Auth::user()->isAdmin())
            <li class="nav-item">
                <a class="nav-link" href="#partners" data-bs-toggle="tab"><i class="bx bx-group me-1"></i> Partner Settings</a>
            </li>
            @endif
            <li class="nav-item">
                <a class="nav-link" href="#referral" data-bs-toggle="tab"><i class="bx bx-group me-1"></i> Referral Settings</a>
            </li>


            @endisset
        </ul>

        <div class="tab-content p-0">

            {{-- ABA 1: DETALHES DA CONTA --}}
            <div class="tab-pane fade show active" id="account-details" role="tabpanel">
                <div class="card mb-4">
                    <h5 class="card-header">Account Details</h5>
                    <div class="card-body">
                        {!! Form::model(new App\Models\Account(), [
                        'route' => 'accounts.store',
                        'method' =>  'POST',
                        'id' => 'formAccountSettings',
                        ]) !!}


                        <div class="row g-3">
                            {{-- Campos do formulário da conta --}}
                            <div class="col-md-3">
                                {!! Form::label('name', 'Account name', ['class' => 'form-label']) !!}
                                {!! Form::text('name', null, ['class' => 'form-control']) !!}
                            </div>
                            <div class="col-md-3">
                                {!! Form::label('min_amount_transaction', 'Min. Amount(Pay IN)', ['class' => 'form-label']) !!}
                                {!! Form::text('min_amount_transaction', null, [
                                'class' => 'form-control' . ($errors->has('min_amount_transaction') ? ' is-invalid' : ''),
                                'readonly' => auth()->user()->level !== 'admin'
                                ]) !!}

                                @if($errors->has('min_amount_transaction'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('min_amount_transaction') }}
                                </div>
                                @endif
                            </div>

                            <div class="col-md-3">
                                {!! Form::label('max_amount_transaction', 'Max Amount (Pay OUT)', ['class' => 'form-label']) !!}
                                {!! Form::text('max_amount_transaction', null, [
                                'class' => 'form-control' . ($errors->has('max_amount_transaction') ? ' is-invalid' : ''),
                                'readonly' => auth()->user()->level !== 'admin'
                                ]) !!}

                                @if($errors->has('max_amount_transaction'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('max_amount_transaction') }}
                                </div>
                                @endif
                            </div>
                            <div class="col-md-3">
                                {!! Form::label('partner_id', 'Partner', ['class' => 'form-label']) !!}
                                {!! Form::select('partner_id',
                                ['' => 'Select a Partner'] + $availablePartners->pluck('name', 'id')->toArray(),
                                old('partner_id', $model->partner_id ?? null),
                                [
                                'class' => 'form-control' . ($errors->has('partner_id') ? ' is-invalid' : ''),
                                'disabled' => auth()->user()->level !== 'admin'
                                ]
                                ) !!}

                                @if($errors->has('partner_id'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('partner_id') }}
                                </div>
                                @endif

                                {{-- Se o campo estiver desabilitado, precisa de um hidden para enviar o valor --}}
                                @if(auth()->user()->level !== 'admin' && isset($model) && $model->partner_id)
                                {!! Form::hidden('partner_id', $model->partner_id) !!}
                                @endif
                            </div>

                            <div class="col-md-3">
                                {!! Form::label('acquirer_id', 'Bank', ['class' => 'form-label']) !!}
                                {!! Form::select('acquirer_id',
                                ['' => 'Select a Bank'] + $banks->pluck('name', 'id')->toArray(),
                                old('acquirer_id', $model->acquirer_id ?? null),
                                [
                                'class' => 'form-control' . ($errors->has('acquirer_id') ? ' is-invalid' : ''),
                                'disabled' => auth()->user()->level !== 'admin'
                                ]
                                ) !!}

                                @if($errors->has('acquirer_id'))
                                <div class="invalid-feedback">
                                    {{ $errors->first('acquirer_id') }}
                                </div>
                                @endif

                                {{-- Se o campo estiver desabilitado, precisa de um hidden para enviar o valor --}}
                                @if(auth()->user()->level !== 'admin' && isset($model) && $model->acquirer_id)
                                {!! Form::hidden('acquirer_id', $model->acquirer_id) !!}
                                @endif
                            </div>
                        </div>

                        {{-- Campos para criar o primeiro usuário (só no modo 'create') --}}
                        
                        <hr class="my-4">
                        <h5>User for account</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="user_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="user_name" name="user_name">
                            </div>
                            <div class="col-md-4">
                                <label for="user_email" class="form-label">Email </label>
                                <input type="email" class="form-control" id="user_email" name="user_email">
                            </div>
                            <div class="col-md-4 form-password-toggle">
                                <label class="form-label" for="user_password">Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="user_password" class="form-control" name="user_password" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                            </div>
                        </div>
                        

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2">{{ isset($account) ? 'Save changes' : 'Create Account' }}</button>
                            <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">Back</a>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>

        
        </div>
    </div>
</div>
@endsection