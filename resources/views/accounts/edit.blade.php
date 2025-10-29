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


            @endisset
        </ul>

        <div class="tab-content p-0">

            {{-- ABA 1: DETALHES DA CONTA --}}
            <div class="tab-pane fade show active" id="account-details" role="tabpanel">
                <div class="card mb-4">
                    <h5 class="card-header">Account Details</h5>
                    <div class="card-body">
                        {!! Form::model($account ?? new App\Models\Account(), [
                        'route' => (isset($account) && $account->exists) ? ['accounts.update', $account->id] : 'accounts.store',
                        'method' => (isset($account) && $account->exists) ? 'PUT' : 'POST',
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

                            @if (Auth::user()->isAdmin())
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
                            @endif
                        </div>

                        {{-- Campos para criar o primeiro usuário (só no modo 'create') --}}
                        @unless(isset($account))
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
                        @endunless

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2">{{ isset($account) ? 'Save changes' : 'Create Account' }}</button>
                            <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">Back</a>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>

            {{-- As abas de detalhes só existem no modo de edição --}}
            @isset($account)
            {{-- ABA 2: USUÁRIOS --}}
            {{-- ABA 2: USUÁRIOS (com modal) --}}
            <div class="tab-pane fade" id="users" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Users account</h5>
                        {{-- [MODIFICADO] Este botão agora abre o modal --}}
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bx bx-plus me-1"></i> New User
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>E-mail</th>
                                        <th>Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                {{-- [NOVO] Adicionado ID ao tbody para fácil manipulação com JS --}}
                                <tbody id="account-users-table-body">
                                    @forelse ($account->users as $user)
                                    @include('accounts.partials.user-row', ['user' => $user, 'account' => $account])
                                    @empty
                                    <tr id="no-users-row">
                                        <td colspan="4" class="text-center">No users associated with this account.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- [NOVO] HTML do Modal para Adicionar Usuário --}}
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserModalLabel">New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        {{-- Formulário com ID para o JS --}}
                        <form id="formAddUserToAccount" action="{{ route('accounts.users.add', $account) }}" method="POST">
                            <div class="modal-body">
                                @csrf
                                <div class="mb-3">
                                    <label for="user_name" class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user_email" class="form-label">E-mail</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user_password" class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user_role" class="form-label">Role</label>
                                    <select name="role" class="form-select" required>
                                        <option value="member">Member</option>
                                        <option value="admin">Admin</option>
                                        <option value="owner">Owner</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ABA 3: TAXAS --}}
            <div class="tab-pane fade" id="fees" role="tabpanel">
                <div class="card">
                    <h5 class="card-header">Manage Fee Profiles</h5>
                    <div class="card-body">

                        {{-- Bloco para exibir mensagens de sucesso ou erro --}}
                        @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        @if (Auth::user()->isAdmin())
                        {{-- Formulário para associar um novo perfil --}}
                        <h6 class="mb-3">Assign New Fee Profile</h6>
                        <form action="{{ route('accounts.fee-profiles.attach', $account->id) }}" method="POST" class="row g-3 align-items-end border p-3 rounded  mb-5">
                            @csrf
                            <div class="col-md-5">
                                <label for="fee_profile_id" class="form-label">Select Profile</label>
                                <select name="fee_profile_id" id="fee_profile_id" class="form-select" required>
                                    <option value="" disabled selected>Choose a profile...</option>
                                    {{-- A variável $availableProfiles deve ser passada pelo controller --}}
                                    @foreach($availableProfiles as $profile)
                                    <option value="{{ $profile->id }}">{{ $profile->name }} ({{ $profile->calculation_type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label for="transaction_type" class="form-label">Transaction Type</label>
                                <select name="transaction_type" id="transaction_type" class="form-select" required>
                                    <option value="DEFAULT">DEFAULT (For all types)</option>
                                    <option value="IN">IN (Incoming)</option>
                                    <option value="OUT">OUT (Outgoing)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Assign</button>
                            </div>
                        </form>
                        @endif

                        {{-- Tabela de Histórico de Perfis --}}
                        <h6 class="mb-3">Fee Assignment History</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Status</th>
                                        <th>Transaction Type</th>
                                        <th>Profile Name & Fee Details</th>
                                        <th>Assigned On</th>
                                        @if (Auth::user()->isAdmin())
                                        <th>Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Ordena para mostrar os ativos primeiro, e depois por data --}}
                                    @forelse($account->feeProfiles->sortByDesc('pivot.status')->sortByDesc('pivot.created_at') as $profile)
                                    <tr>
                                        <td>
                                            @if($profile->pivot->status == 'active')
                                            <span class="badge bg-success">Active</span>
                                            @else
                                            <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-dark">{{ $profile->pivot->transaction_type }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $profile->name }}</strong>
                                            <div class="text-muted small mt-1">
                                                @switch($profile->calculation_type)
                                                @case('SIMPLE_FIXED')
                                                <span>Fixed Fee: <strong>R$ {{ number_format($profile->fixed_fee, 2, ',', '.') }}</strong></span>
                                                @break

                                                @case('GREATER_OF_BASE_PERCENTAGE')
                                                <span>Base Fee: <strong>R$ {{ number_format($profile->base_fee, 2, ',', '.') }}</strong></span>
                                                <span class="mx-1">/</span>
                                                <span>Percentage: <strong>{{ number_format($profile->percentage_fee, 2, ',', '.') }}%</strong></span>
                                                @break

                                                @case('TIERED')
                                                <span>Tiered Pricing Details:</span>
                                                {{-- ======================================================= --}}
                                                {{-- == NOVA LÓGICA PARA EXIBIR AS FAIXAS (TIERS) == --}}
                                                {{-- ======================================================= --}}
                                                @if($profile->tiers->isEmpty())
                                                <div class="ps-2">- No tiers configured.</div>
                                                @else
                                                {{-- Usamos sortBy para garantir que as faixas apareçam em ordem --}}
                                                @foreach($profile->tiers->sortBy('min_value') as $tier)
                                                <div class="ps-3">
                                                    <span class="text-nowrap">
                                                        • R$ {{ number_format($tier->min_value, 2, ',', '.') }} to {{ $tier->max_value ? 'R$ ' . number_format($tier->max_value, 2, ',', '.') : 'Above' }}:
                                                    </span>
                                                    <strong class="ms-1">
                                                        {{-- Lógica para mostrar as taxas da faixa --}}
                                                        @if(!is_null($tier->fixed_fee)) R$ {{ number_format($tier->fixed_fee, 2, ',', '.') }} @endif
                                                        @if(!is_null($tier->fixed_fee) && !is_null($tier->percentage_fee)) + @endif
                                                        @if(!is_null($tier->percentage_fee)) {{ number_format($tier->percentage_fee, 2, ',', '.') }}% @endif
                                                    </strong>
                                                </div>
                                                @endforeach
                                                @endif
                                                @break
                                                @endswitch
                                            </div>
                                        </td>
                                        <td>{{ $profile->pivot->created_at->format('d/m/Y H:i') }}</td>
                                        @if (Auth::user()->isAdmin())
                                        <td>
                                            {{-- O botão de remover agora só aparece para regras ativas, para evitar confusão --}}
                                            @if($profile->pivot->status == 'active')
                                            <form action="{{ route('accounts.fee-profiles.detach', [$account->id, $profile->id]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                {{-- Adicionando o tipo de transação para saber qual vínculo exato remover --}}
                                                <input type="hidden" name="transaction_type" value="{{ $profile->pivot->transaction_type }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to set this profile as inactive?')">Deactivate</button>
                                            </form>
                                            @endif
                                        </td>
                                        @endif
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No fee profiles have been assigned to this account yet.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ABA 4: CHAVES PIX --}}
            <div class="tab-pane fade" id="pix-keys" role="tabpanel">
                <div class="card">
                    <h5 class="card-header">PIX Key Management</h5>
                    <div class="card-body">
                        <h6>Add New PIX Key</h6>
                        <p class="text-muted small">These keys will be available for withdrawals.</p>

                        {{-- ✅ Formulário com ID para o JavaScript --}}
                        <form id="formAddPixKey" action="{{ route('account-pix-keys.store', ['user' => $account->id]) }}" method="POST">
                            @csrf
                            <input type="hidden" id="account_id" value="{{ $account->id }}">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="pix_key_type" class="form-label">Key Type</label>
                                    <select id="pix_key_type" name="type" class="form-select" required>
                                        <option value="EMAIL">E-mail</option>
                                        <option value="PHONE">Phone</option>
                                        <option value="CPF">CPF</option>
                                        <option value="CNPJ">CNPJ</option>
                                        <option value="EVP">Random Key (EVP)</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label for="pix_key_value" class="form-label">Key</label>
                                    <input type="text" id="pix_key_value" name="key" class="form-control" placeholder="Enter the PIX key" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Key</button>
                        </form>
                        <hr>
                        <h6>Registered Keys</h6>

                        {{-- ✅ Lista com ID e um item "template" para quando estiver vazia --}}
                        <ul class="list-group" id="pix-key-list">
                            @forelse($account->pixKeys as $pixKey)
                            <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $pixKey->id }}">
                                <span><strong>{{ $pixKey->type }}:</strong> {{ $pixKey->key }}</span>
                                <form action="{{ route('account-pix-keys.destroy', ['pixKey' => $pixKey->id]) }}" method="POST" class="form-delete-pix-key">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </li>
                            @empty
                            <li class="list-group-item text-muted" id="no-pix-keys-message">No PIX keys registered.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- ABA: DEVELOPER (ADAPTADA PARA ACCOUNT E AJAX) --}}
            <div class="tab-pane fade" id="developer" role="tabpanel">
                <div class="card">
                    <h5 class="card-header">Developer Settings</h5>
                    <div class="card-body">
                        {{-- Seção da Base URL (sem alterações) --}}
                        <div class="row">
                            <div class="mb-3 col-md-12">
                                <label for="base-url" class="form-label">Base URL</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="base-url" value="https://hub.getpay.one/api" readonly>
                                    <span class="input-group-text" style="cursor: pointer;" onclick="copiarTexto('base-url')">
                                        <i class="bx bx-copy"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        @isset($account)
                        <hr class="my-4">
                        <h6>Add Webhook</h6>
                        {{-- [MODIFICADO] Adicionado ID ao formulário --}}
                        <form id="formAddWebhook" action="{{ route('accounts.webhooks.store', $account) }}" method="POST">
                            @csrf
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label for="url">URL Webhook</label>
                                    <input type="url" name="url" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="event">Transaction Type</label>
                                    <select name="event" class="form-select" required>
                                        <option value="IN">IN (Deposits)</option>
                                        <option value="OUT">OUT (Withdrawals)</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Webhook</button>
                        </form>

                        <hr class="my-4">
                        <h6>Registered Webhooks</h6>
                        {{-- [MODIFICADO] ID adicionado à lista para fácil manipulação --}}
                        <ul class="list-group" id="webhook-list">
                            @forelse($account->webhooks as $webhook)
                            @include('_partials.webhook-item', ['webhook' => $webhook])
                            @empty
                            <li class="list-group-item text-muted" id="no-webhooks-message">No webhooks registered for this account.</li>
                            @endforelse
                        </ul>
                        @else
                        <p class="text-muted mt-3">Webhook settings will be available after the account is created.</p>
                        @endisset

                        {{-- ✅ [SEÇÃO API CREDENTIALS] Verifique se este bloco existe na sua view --}}
                        <div class="card mt-4">
                            <h5 class="card-header">API Credentials (V2)</h5>
                            <div class="card-body">
                                <p>Manage the Client ID and Client Secret for API V2 access. Regenerating credentials will invalidate the previous ones immediately.</p>

                                {{-- Mostra o Client ID Atual --}}
                                <div class="mb-3 row">
                                    <label for="currentApiClientId" class="col-md-3 col-form-label">Current Client ID:</label>
                                    <div class="col-md-9">
                                        <input type="text" readonly class="form-control-plaintext" id="currentApiClientId" value="{{ $account->api_client_id ?? 'Not generated yet' }}">
                                    </div>
                                </div>

                                {{-- Botão para Gerar Novas Credenciais --}}
                                <button type="button" class="btn btn-primary" id="generateApiCredentialsBtn" data-url="{{ route('admin.accounts.generateApiCredentials', $account) }}">
                                    Generate New Credentials
                                </button>

                                {{-- Área Para mostrar as credenciais geradas (começa escondida) --}}
                                <div id="newCredentialsSection" class="mt-4 p-3 border rounded bg-light" style="display: none;">
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="bx bx-error-circle me-2"></i>
                                        <div>
                                            <strong>Important:</strong> The Client Secret is shown only once. Copy it now and store it securely. Your previous credentials are now invalid.
                                        </div>
                                    </div>
                                    {{-- Client ID com botão de copiar --}}
                                    <div class="mb-2 row">
                                        <label for="newApiClientId" class="col-sm-3 col-form-label">New Client ID:</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="text" readonly class="form-control" id="newApiClientId" value="">
                                                <button class="btn btn-outline-secondary btn-copy" type="button" data-clipboard-target="#newApiClientId" title="Copy Client ID">
                                                    <i class="bx bx-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Client Secret com botão de copiar --}}
                                    <div class="row">
                                        <label for="newApiClientSecret" class="col-sm-3 col-form-label">New Client Secret:</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input type="text" readonly class="form-control" id="newApiClientSecret" value="">
                                                <button class="btn btn-outline-secondary btn-copy" type="button" data-clipboard-target="#newApiClientSecret" title="Copy Client Secret">
                                                    <i class="bx bx-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- Fim da área de novas credenciais --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ABA: PARTNERS --}}
            {{-- ABA: SÓCIOS E COMISSÕES (COM AJAX) --}}
            <div class="tab-pane fade " id="partners" role="tabpanel">
                <div class="card">
                    <h6 class="card-header">Partners with Participation in this Account</h6>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap mb-4">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th class="text-end"> Commission on Profit</th>
                                        <th class="text-end">Platform Withdrawal Fee</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                {{-- [MODIFICADO] Adicionado ID ao tbody --}}
                                <tbody id="partners-table-body">
                                    @forelse ($account->profitSharingPartners as $partner)
                                    {{-- Usando a nova partial --}}
                                    @include('accounts.partials.partner-row', ['account' => $account, 'partner' => $partner])
                                    @empty
                                    <tr id="no-partners-row">
                                        <td colspan="4" class="text-center">No partners with participation in this account.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <hr class="my-4">

                        <h6 class="mt-4">New Partner</h6>
                        {{-- [MODIFICADO] Adicionado ID ao formulário --}}
                        <form id="formAttachPartner" action="{{ route('accounts.partners.attach', $account) }}" method="POST">
                            @csrf
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="partner_id_select" class="form-label">Select Member</label>
                                    {{-- [MODIFICADO] Adicionado ID ao select --}}
                                    <select name="partner_id" id="partner_id_select" class="form-select" required>
                                        <option value="" disabled selected>Choose a partner...</option>
                                        @foreach ($availablePartners as $partner)
                                        <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="commission_rate" class="form-label">Partner Commission (%)</label>
                                    <input type="number" name="commission_rate" class="form-control" placeholder="25" step="0.01" min="0" max="100" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="platform_withdrawal_fee_rate" class="form-label">Withdrawal Rate (%)</label>
                                    <input type="number" name="platform_withdrawal_fee_rate" class="form-control" placeholder="10" step="0.01" min="0" max="100" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary">Add</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endisset

        </div>
    </div>
</div>
@endsection