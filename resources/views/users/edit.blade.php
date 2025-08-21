@extends('layouts/contentNavbarLayout')

@section('title', 'Account settings - ' . ($user->name ?? 'New User'))


@section('page-script')
@vite('resources/assets/js/pages-account-settings-account.js')
@vite('resources/assets/js/verify-pass.js')
@vite('resources/assets/js/copy.js')
@vite('resources/assets/js/account-2fa.js')
@vite('resources/assets/js/pix-key-manager.js')
@vite('resources/assets/js/partner-payout-manager.js')
@endsection

@section('content')

<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Users /</span> Edit
</h4>

<div class="row">
    <div class="col-md-12">

        {{-- NAVEGAÇÃO POR ABAS --}}
        <ul class="nav nav-pills flex-column flex-md-row mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="#account-details" data-bs-toggle="tab"><i class="bx bx-user me-1"></i> Profile</a>
            </li>
            @if (isset($user))
            <li class="nav-item">
                <a class="nav-link" href="#security" data-bs-toggle="tab"><i class="bx bx-lock-alt me-1"></i> Security</a>
            </li>
            @endif
            @if (isset($user) && $user->isPartner())
            <li class="nav-item">
                <a class="nav-link" href="#payout-methods" data-bs-toggle="tab">
                    <i class="bx bx-credit-card me-1"></i> Payout Methods
                </a>
            </li>
            @endif
        </ul>

        <div class="tab-content p-0">

            {{-- =================================================================== --}}
            {{-- ABA 1: DETALHES DA CONTA                                           --}}
            {{-- =================================================================== --}}
            <div class="tab-pane fade show active" id="account-details" role="tabpanel">
                <div class="card mb-4">
                    <h5 class="card-header">User Details</h5>
                    <div class="card-body">
                        {!! Form::model($user ?? new App\Models\User(), [
                        'route' => $user && $user->id ? ['users.update', $user->id] : 'users.store',
                        'method' => $user && $user->id ? 'PUT' : 'POST',
                        'id' => 'formAccountSettings',
                        ]) !!}
                        <div class="row g-3">
                            <div class="col-md-3">
                                {!! Form::label('name', 'Name', ['class' => 'form-label']) !!}
                                {!! Form::text('name', null, ['class' => 'form-control' . ($errors->has('name') ? ' is-invalid' : ''), 'autofocus']) !!}
                                {{-- ✅ Bloco para exibir o erro de validação --}}
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                {!! Form::label('email', 'E-mail', ['class' => 'form-label']) !!}
                                {!! Form::text('email', null, ['class' => 'form-control' . ($errors->has('email') ? ' is-invalid' : '')]) !!}
                                {{-- ✅ Bloco para exibir o erro de validação --}}
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                {!! Form::label('document', 'Document', ['class' => 'form-label']) !!}
                                {!! Form::text('document', null, ['class' => 'form-control' . ($errors->has('document') ? ' is-invalid' : '')]) !!}
                                {{-- ✅ Bloco para exibir o erro de validação --}}
                                @error('document')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if ($accounts)
                            <div class="col-md-3">
                                <label for="accounts" class="form-label">
                                    Associate with Account</label>
                                <select id="accounts" name="accounts" class="form-select" @if(auth()->user()->level != 'admin') disabled @endif>
                                    <option value="" disabled @selected(!isset($user) || $user->accounts->isEmpty())>
                                        Select an account...
                                    </option>
                                    @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}"
                                        @selected(
                                        in_array(
                                        $account->id,
                                        old('accounts', isset($user) ? $user->accounts->pluck('id')->toArray() : [])
                                        )
                                        )>
                                        {{ $account->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif


                            <div class="col-md-3">
                                {!! Form::label('level', 'Level', ['class' => 'form-label']) !!}
                                {!! Form::select('level', ['admin' => 'Admin', 'client' => 'Client', 'partner' => 'Operator'], $user->level ?? 'client', ['class' => 'form-select' . ($errors->has('level') ? ' is-invalid' : ''), 'disabled' => Auth::user()->level !== 'admin']) !!}
                                {{-- ✅ Bloco para exibir o erro de validação --}}
                                @error('level')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                {!! Form::label('status', 'Status', ['class' => 'form-label']) !!}
                                {!! Form::select('status', ['0' => 'Inactive', '1' => 'Active'], $user->status ?? 1, ['class' => 'form-select' . ($errors->has('status') ? ' is-invalid' : ''), 'disabled' => Auth::user()->level !== 'admin']) !!}
                                {{-- ✅ Bloco para exibir o erro de validação --}}
                                @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if(!isset($user))
                            <div class="col-md-6 form-password-toggle">
                                <label class="form-label" for="password">Password</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password" required />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                                @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                            @endif
                        </div>
                        <div class="mt-4">
                            {!! Form::submit('Save Profile Changes', ['class' => 'btn btn-primary me-2']) !!}
                            <a href="{{ route('users') }}" class="btn btn-outline-secondary">Back</a>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
            @if (isset($user))


            {{-- =================================================================== --}}
            {{-- ABA 2: SEGURANÇA (SENHA E 2FA)                                     --}}
            {{-- =================================================================== --}}
            <div class="tab-pane fade" id="security" role="tabpanel">
                {{-- Card de Senha --}}
                {{-- Card de Senha --}}
                {{-- Card de Senha --}}
                <div class="card mb-4">
                    <h5 class="card-header">Change Password</h5>
                    <div class="card-body">
                        <form id="formPasswordChange" action="{{ route('users.password.update', $user->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                {{-- ✅ ESTRUTURA ORIGINAL DO TEMA RESTAURADA --}}
                                <div class="mb-3 col-md-6 form-password-toggle">
                                    <label class="form-label" for="password">New Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" autocomplete="new-password" />
                                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                    </div>
                                    {{-- Ganchos para o script JS injetar o feedback --}}
                                    <div class="password-strength-meter mt-2"></div>
                                    <div class="password-feedback"></div>
                                </div>

                                {{-- ✅ ESTRUTURA ORIGINAL DO TEMA RESTAURADA --}}
                                <div class="mb-3 col-md-6 form-password-toggle">
                                    <label class="form-label" for="password_confirmation">Confirm New Password</label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" autocomplete="new-password" />
                                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                    </div>
                                    {{-- Gancho para o script JS injetar o feedback --}}
                                    <div class="confirm-password-feedback"></div>
                                </div>

                                <div class="col-12 mt-1">
                                    <button type="submit" class="btn btn-primary save-btn" disabled>Update Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Card de 2FA (Aparece apenas se for o próprio usuário logado) --}}
                @if ($user && $user->id && Auth::id() == $user->id)
                {{-- Card de 2FA Refatorado para AJAX --}}
                <div class="card" id="twoFactorAuthCard">
                    <h5 class="card-header">Two-Factor Authentication (2FA)</h5>
                    <div class="card-body">

                        <div id="tfaDisabledSection" class="tfa-section" style="{{ $user->two_factor_secret ? 'display:none;' : '' }}">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading mb-1">2FA is Disabled</h6>
                                <p class="mb-0">Enable 2FA to add an extra layer of security.</p>
                            </div>
                            <form id="formEnable2fa" method="POST" action="{{ route('user.2fa.enable') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary mt-2">Enable 2FA</button>
                            </form>
                        </div>

                        <div id="tfaConfirmationSection" class="tfa-section" style="display:none;">
                            <div class="alert alert-info">
                                <h6 class="alert-heading mb-1">Confirm 2FA Setup</h6>
                                <p class="mb-0">Scan the QR code below, then enter the 6-digit code from your app.</p>
                            </div>
                            <div id="qrCodeContainer" class="text-center my-4"></div>
                            <p class="text-center mb-4">
                                <strong>Or enter setup key manually:</strong><br>
                                <code id="secretKeyContainer" class="fs-5 p-2 bg-light rounded d-inline-block mt-1"></code>
                            </p>
                            <form id="formConfirm2fa" method="POST" action="{{ route('user.2fa.confirm') }}">
                                @csrf
                                <div class="row justify-content-center">
                                    <div class="col-md-6">
                                        <label for="2fa_code" class="form-label">Verification Code</label>
                                        <input id="2fa_code" type="text" class="form-control" name="code" required autocomplete="one-time-code" maxlength="6" placeholder="123456">
                                        <div class="d-grid mt-3">
                                            <button type="submit" class="btn btn-primary">Confirm & Enable 2FA</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div id="tfaEnabledSection" class="tfa-section" style="{{ $user->two_factor_secret ? '' : 'display:none;' }}">
                            <div class="alert alert-success">
                                <h6 class="alert-heading mb-1">2FA is Enabled</h6>
                                <p class="mb-0">Two-Factor Authentication is active for your account.</p>
                            </div>
                            {{-- Aviso para os códigos de recuperação --}}
                            <div id="recoveryCodesInfo" class="alert alert-danger" style="display:none;">
                                <h6 class="alert-heading mb-1">Save Your Recovery Codes!</h6>
                                <p>Store these codes in a safe place. They can be used to regain access to your account if you lose your device.</p>
                                <ul id="recoveryCodesList" class="mb-0"></ul>
                            </div>
                            <form id="formDisable2fa" method="POST" action="{{ route('user.2fa.disable') }}">
                                @csrf
                                <button type="submit" class="btn btn-danger">Disable 2FA</button>
                            </form>
                        </div>

                    </div>
                </div>
                @endif
            </div>


            {{-- Dentro de <div class="tab-content"> --}}
            @if (isset($user) && $user->isPartner())
            <div class="tab-pane fade" id="payout-methods" role="tabpanel">
                <div class="card">
                    <h5 class="card-header">Payout Methods Management</h5>
                    <div class="card-body">
                        <h6>Add New PIX Key</h6>
                        <form id="formAddPayoutMethod" action="{{ route('partner-payout-methods.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="partner_id" value="{{ $user->id }}">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4"><label for="pix_key_type" class="form-label">Key Type</label><select id="pix_key_type" name="type" class="form-select" required>
                                        <option value="EMAIL">E-mail</option>
                                        <option value="PHONE">Phone</option>
                                        <option value="CPF">CPF</option>
                                        <option value="CNPJ">CNPJ</option>
                                        <option value="EVP">Random Key (EVP)</option>
                                    </select></div>
                                <div class="col-md-8"><label for="pix_key_value" class="form-label">Key</label><input type="text" id="pix_key_value" name="key" class="form-control" placeholder="Enter the PIX key" required></div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Key</button>
                        </form>
                        <hr>
                        <h6>Registered Keys</h6>
                        <ul class="list-group" id="payout-method-list">
                            @forelse($user->payoutMethods as $payoutMethod)
                            @include('users.partials.payout-method-row', ['payoutMethod' => $payoutMethod])
                            @empty
                            <li class="list-group-item text-muted" id="no-payout-methods-message">No payout methods registered.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            @endif
            @endif

        </div>
    </div>

    {{-- CARD DE DELETAR CONTA (MANTIDO FORA DAS ABAS) --}}
    @if ($user && $user->id && Auth::user()->level == 'admin' && Auth::id() != $user->id)
    <div class="card mt-4">
        <h5 class="card-header text-danger">Delete Account</h5>
        <div class="card-body">
            <div class="mb-3 col-12 mb-0">
                <div class="alert alert-danger">
                    <h5 class="alert-heading mb-1">Are you sure you want to delete this user's account?</h5>
                    <p class="mb-0">This action is irreversible.</p>
                </div>
            </div>
            <form id="formAccountDeletion" action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('This will permanently delete the user. Are you absolutely sure?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete This User Account</button>
            </form>
        </div>
    </div>
    @endif

</div>
</div>
@endsection