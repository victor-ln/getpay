@extends('layouts/contentNavbarLayout')

@section('title', 'Banks')

@section('page-script')
@vite(['resources/assets/js/pages-account-settings-account.js'])
@endsection

@section('content')

@if(session('error'))
<div class="alert alert-danger alert-dismissible" role="alert">
    <strong>Erro:</strong> {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="nav-align-top">
    <ul class="nav nav-pills flex-column flex-md-row mb-6">
        <li class="nav-item"><a class="nav-link active" href="javascript:void(0);"><i
                    class="bx bx-sm bxs-bank me-1_5"></i> Bank</a></li>
        <li class="nav-item"><a class="nav-link " href="{{ route('banks.index') }}"><i
                    class="bx bx-sm bx-arrow-back me-1_5"></i> Back</a></li>
    </ul>
</div>
<div class="card">

    <h5 class="card-header">Banks/Acquirers</h5>

    <div class="card-body">
        @if(isset($bank))
        {!! Form::model($bank, [
        'route' => ['banks.update', $bank->id],
        'method' => 'PATCH',
        'files' => true // IMPORTANTE: permite upload de arquivos
        ]) !!}
        @else
        {!! Form::open(['route' => 'banks.store', 'files' => true]) !!}
        @endif

        {{-- Seção de Dados do Banco --}}
        <h6 class="mb-4 text-muted">Bank Details</h6>

        <div class="mb-3">
            {!! Form::label('baseurl', 'Base URL', ['class' => 'form-label']) !!}
            {!! Form::text('baseurl', null, ['class' => 'form-control', 'required']) !!}
            @error('baseurl')
            <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                {!! Form::label('name', 'Name', ['class' => 'form-label']) !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'required']) !!}
                @error('name')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                {!! Form::label('token', 'Token', ['class' => 'form-label']) !!}
                {!! Form::text('token', null, ['class' => 'form-control']) !!}
                @error('token')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                {!! Form::label('user', 'Username', ['class' => 'form-label']) !!}
                {!! Form::text('user', null, ['class' => 'form-control']) !!}
                @error('user')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                {!! Form::label('password', 'Password', ['class' => 'form-label']) !!}
                {!! Form::password('password', ['class' => 'form-control']) !!}
                @error('password')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                {!! Form::label('client_id', 'Client ID', ['class' => 'form-label']) !!}
                {!! Form::text('client_id', null, ['class' => 'form-control']) !!}
                @error('client_id')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6">
                {!! Form::label('client_secret', 'Client Secret', ['class' => 'form-label']) !!}
                {!! Form::text('client_secret', null, ['class' => 'form-control']) !!}
                @error('client_secret')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row mb-3">


            <div class="col-md-6">
                {!! Form::label('active', 'Active?', ['class' => 'form-label']) !!}
                {!! Form::select('active', [1 => 'Yes', 0 => 'No'], null, ['class' => 'form-select']) !!}
                @error('active')
                <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr class="my-5"> {{-- Separador --}}




        {{-- ========================================================== --}}
        {{-- Seção de Fees  --}}
        {{-- ========================================================== --}}

        <h5 class="card-header" style="padding-left: 0; padding-bottom: 1.5rem;">Fees Configuration</h5>
        <p class="text-muted" style="margin-top: -1rem; margin-bottom: 1.5rem;">Set the fees your platform will charge for transactions processed by this acquirer. The fixed fee takes precedence over the others.</p>

        <div class="row g-4">
            {{-- Coluna de Depósito (Pay-in) --}}
            <div class="col-md-6">
                <div class="p-3 border rounded h-100">
                    <h6 class="text-success"><i class="bx bx-down-arrow-circle me-1"></i>Deposit Fees (IN)</h6>
                    <hr>
                    <div class="mb-3">
                        <label for="fees_deposit_fixed" class="form-label">Fixed Fee (BRL)</label>
                        <input type="number" name="fees[deposit][fixed]" class="form-control" step="0.01" placeholder="e.g., 0.50"
                            value="{{ old('fees.deposit.fixed', $bank->fees_config['deposit']['fixed'] ?? 0) }}">
                        @error('fees.deposit.fixed') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="fees_deposit_percentage" class="form-label">Percentage Fee (%)</label>
                        <input type="number" name="fees[deposit][percentage]" class="form-control" step="0.01" placeholder="e.g., 2.5"
                            value="{{ old('fees.deposit.percentage', $bank->fees_config['deposit']['percentage'] ?? 0) }}">
                        @error('fees.deposit.percentage') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="fees_deposit_minimum" class="form-label">Minimum Fee (BRL)</label>
                        <input type="number" name="fees[deposit][minimum]" class="form-control" step="0.01" placeholder="e.g., 0.30"
                            value="{{ old('fees.deposit.minimum', $bank->fees_config['deposit']['minimum'] ?? 0) }}">
                        @error('fees.deposit.minimum') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- Coluna de Saque (Pay-out) --}}
            <div class="col-md-6">
                <div class="p-3 border rounded h-100">
                    <h6 class="text-danger"><i class="bx bx-up-arrow-circle me-1"></i>Withdrawal Fees (OUT)</h6>
                    <hr>
                    <div class="mb-3">
                        <label for="fees_withdrawal_fixed" class="form-label">Fixed Fee (BRL)</label>
                        <input type="number" name="fees[withdrawal][fixed]" class="form-control" step="0.01" placeholder="e.g., 5.00"
                            value="{{ old('fees.withdrawal.fixed', $bank->fees_config['withdrawal']['fixed'] ?? 0) }}">
                        @error('fees.withdrawal.fixed') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="fees_withdrawal_percentage" class="form-label">Percentage Fee (%)</label>
                        <input type="number" name="fees[withdrawal][percentage]" class="form-control" step="0.01" placeholder="e.g., 0.5"
                            value="{{ old('fees.withdrawal.percentage', $bank->fees_config['withdrawal']['percentage'] ?? 0) }}">
                        @error('fees.withdrawal.percentage') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="fees_withdrawal_minimum" class="form-label">Minimum Fee (BRL)</label>
                        <input type="number" name="fees[withdrawal][minimum]" class="form-control" step="0.01" placeholder="e.g., 1.00"
                            value="{{ old('fees.withdrawal.minimum', $bank->fees_config['withdrawal']['minimum'] ?? 0) }}">
                        @error('fees.withdrawal.minimum') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ====================================================== --}}
        {{-- ✅ [NOVA SEÇÃO] FORMULÁRIO DE CONFIGURAÇÃO AVANÇADA --}}
        {{-- ====================================================== --}}
        <hr class="my-4">
        <h5 class="mb-1">Configuração de API Avançada</h5>
        <p class="text-muted mb-4">Preencha esta secção para bancos com múltiplas APIs (como o E2). Para bancos simples, pode deixar estes campos em branco.</p>

        {{-- Um campo escondido para dizer ao controller que estes dados devem ser processados --}}
        <input type="hidden" name="is_advanced_config" value="1">

        {{-- SEÇÃO DE PAY IN --}}
        <div class="row g-3 p-3 mb-3 border rounded">
            <h6 class="text-success"><i class="bx bx-down-arrow-circle me-2"></i>Configurações de Pay In</h6>
            <div class="col-12">
                <label class="form-label">URL Base (Pay In)</label>
                <input type="text" name="pay_in_base_url" class="form-control" value="{{ old('pay_in_base_url', $bank->api_config['pay_in']['base_url'] ?? '') }}">
            </div>
            {{-- Credenciais Pay In --}}
            <div class="col-md-6">
                <label class="form-label">Client ID (Pay In)</label>
                <input type="text" name="pay_in_client_id" class="form-control" value="{{ old('pay_in_client_id', $bank->api_config['pay_in']['credentials']['client_id'] ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Client Secret (Pay In)</label>
                <input type="password" name="pay_in_client_secret" class="form-control" placeholder="Deixe em branco para não alterar">
            </div>
            {{-- Certificados Pay In --}}
            <div class="col-md-4">
                <label class="form-label">Certificate (.crt)</label>
                <input type="file" name="pay_in_cert_crt" class="form-control">
                @if(isset($bank) && isset($bank->api_config['pay_in']['certificate']['crt']))
                <small class="form-text text-muted">Atual: {{ Str::afterLast($bank->api_config['pay_in']['certificate']['crt'], '/') }}</small>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Key Certificate (.key)</label>
                <input type="file" name="pay_in_cert_key" class="form-control">
                @if(isset($bank) && isset($bank->api_config['pay_in']['certificate']['key']))
                <small class="form-text text-muted">Atual: {{ Str::afterLast($bank->api_config['pay_in']['certificate']['key'], '/') }}</small>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">PFX Certificate(.pfx)</label>
                <input type="file" name="pay_in_cert_pfx" class="form-control">
                @if(isset($bank) && isset($bank->api_config['pay_in']['certificate']['pfx']))
                <small class="form-text text-muted">Atual: {{ Str::afterLast($bank->api_config['pay_in']['certificate']['pfx'], '/') }}</small>
                @endif
            </div>
            <div class="col-6">
                <label class="form-label">Password of Certificate (Pay In)</label>
                <input type="password" name="pay_in_cert_pass" class="form-control" placeholder="Deixe em branco para não alterar">
            </div>

            <div class="col-6 ">
                <label class="form-label">PIX Key</label>
                <input type="text" name="pay_in_pix_key" class="form-control"
                    value="{{ old('pay_in_pix_key', $bank->api_config['pay_in']['pix_key'] ?? '') }}"
                    placeholder="Chave PIX da sua conta nesta liquidante">
                <div class="form-text text-warning">This is the PIX key that will be used to generate PIX charges for our customers.</div>
            </div>
        </div>

        {{-- SEÇÃO DE PAY OUT --}}
        <div class="row g-3 p-3 border rounded">
            <h6 class="text-danger"><i class="bx bx-up-arrow-circle me-2"></i>Configurações de Pay Out</h6>
            <div class="col-12">
                <label class="form-label">URL Base (Pay Out)</label>
                <input type="text" name="pay_out_base_url" class="form-control" value="{{ old('pay_out_base_url', $bank->api_config['pay_out']['base_url'] ?? '') }}">
            </div>
            {{-- Credenciais Pay In --}}
            <div class="col-md-6">
                <label class="form-label">Client ID (Pay Out)</label>
                <input type="text" name="pay_out_client_id" class="form-control" value="{{ old('pay_out_client_id', $bank->api_config['pay_out']['credentials']['client_id'] ?? '') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Client Secret (Pay Out)</label>
                <input type="password" name="pay_out_client_secret" class="form-control" placeholder="Deixe em branco para não alterar">
            </div>
            {{-- Certificados Pay In --}}
            <div class="col-md-4">
                <label class="form-label">Certificate (.crt)</label>
                <input type="file" name="pay_out_cert_crt" class="form-control">
                @if(isset($bank) && isset($bank->api_config['pay_out']['certificate']['crt']))
                <small class="form-text text-muted">Atual: {{ Str::afterLast($bank->api_config['pay_out']['certificate']['crt'], '/') }}</small>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Certificate key (.key)</label>
                <input type="file" name="pay_out_cert_key" class="form-control">
                @if(isset($bank) && isset($bank->api_config['pay_out']['certificate']['key']))
                <small class="form-text text-muted">Atual: {{ Str::afterLast($bank->api_config['pay_out']['certificate']['key'], '/') }}</small>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label">Certificate personal (.pfx)</label>
                <input type="file" name="pay_out_cert_pfx" class="form-control">
                @if(isset($bank) && isset($bank->api_config['pay_out']['certificate']['pfx']))
                <small class="form-text text-muted">Atual: {{ Str::afterLast($bank->api_config['pay_out']['certificate']['pfx'], '/') }}</small>
                @endif
            </div>
            <div class="col-12">
                <label class="form-label">Password of Certificate (Pay Out)</label>
                <input type="password" name="pay_out_cert_pass" class="form-control" placeholder="Deixe em branco para não alterar">
            </div>
        </div>

        <hr class="my-5"> {{-- Separador --}}



        {{-- Seção de KPIs --}}
        @if(isset($bank) && isset($kpis))
        <div class="card mt-4">
            <h5 class="card-header">Bank Performance KPIs</h5>
            <div class="card-body">
                <div class="row">
                    {{-- Coluna da Esquerda: Métricas Financeiras Principais --}}
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center ps-0">
                                <span><i class="bx bx-wallet me-2"></i>Current Balance in Acquirer</span>
                                <strong class="text-primary fs-5">{{ number_format($kpis['current_balance'], 2, ',', '.') }} BRL</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center ps-0">
                                <span><i class="bx bx-dollar-circle me-2"></i>Total Fees Earned</span>
                                <strong class="text-success">+ {{ number_format($kpis['total_fees_paid'], 2, ',', '.') }} BRL</strong>
                            </li>
                        </ul>
                    </div>

                    {{-- Coluna da Direita: Volume de Transações Mensal --}}
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center ps-0">
                                <span><i class="bx bx-trending-up me-2 text-success"></i>Volume Entered This Month</span>
                                <span class="text-muted">{{ number_format($kpis['in_this_month'], 2, ',', '.') }} BRL</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center ps-0">
                                <span><i class="bx bx-trending-down me-2 text-danger"></i>Volume Exited This Month</span>
                                <span class="text-muted">{{ number_format($kpis['out_this_month'], 2, ',', '.') }} BRL</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {!! Form::submit(isset($bank) ? 'Update' : 'Save', ['class' => 'btn btn-primary me-2 mt-4']) !!}



                {!! Form::close() !!}
    </div>
</div>
@endsection