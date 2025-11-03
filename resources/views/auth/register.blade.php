@extends('layouts/blankLayout')

@section('title', 'Register')

@section('page-style')
@vite([
'resources/assets/vendor/scss/pages/page-auth.scss'
])
@endsection

@section('page-script')
@vite('resources/assets/js/verify-pass.js')
@endsection


@section('content')
<div class="container-xxl">
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">
            <!-- Register Card -->
            <div class="card px-sm-6 px-0">
                <div class="card-body">
                    <!-- Logo -->
                    <div class="app-brand justify-content-center mb-6">
                        <a href="{{url('/')}}" class="app-brand-link gap-2">
                            <span class="app-brand-logo demo">@include('_partials.macros',["width"=>25,"withbg"=>'var(--bs-primary)'])</span>
                            <span class="app-brand-text demo text-heading fw-bold">{{config('variables.templateName')}}</span>
                        </a>
                    </div>
                    <!-- /Logo -->
                    <h4 class="mb-1">Start Managing Your Finances </h4>
                    <p class="mb-6">Create your account and take control of your payments today!</p>

                    {{-- ✅ [A ADIÇÃO] Mostra quem indicou, se existir --}}
                    @if (isset($affiliate_name) && $affiliate_name)
                    <div class="alert alert-info" role="alert">
                        You are being referred by: <strong>{{ $affiliate_name }}</strong>
                    </div>
                    @endif

                    <form id="formAuthentication" class="mb-6" action="{{ route('register') }}" method="POST">
                        @csrf

                        {{-- ✅ [A ADIÇÃO] Passa o código de referência de forma oculta, se existir --}}
                        @if (isset($referral_code) && $referral_code)
                        <input type="hidden" name="referral_code" value="{{ $referral_code }}">
                        @endif

                        {{-- Campo: Nome do Utilizador --}}
                        <div class="mb-6">
                            <label for="name" class="form-label">Your Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Enter your full name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ✅ [NOVO CAMPO] Nome da Conta --}}
                        <div class="mb-6">
                            <label for="account_name" class="form-label">Account Name</label>
                            <input type="text" class="form-control @error('account_name') is-invalid @enderror" id="account_name" name="account_name" placeholder="Enter your company or account name" value="{{ old('account_name') }}" required>
                            @error('account_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Campo: Email --}}
                        <div class="mb-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required>
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Campo: Senha --}}
                        <div class="mb-6 form-password-toggle">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                                <input type="password" id="password" class="form-control" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" required />
                                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                            </div>
                            @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Campo: Confirmar Senha --}}
                        <div class="mb-6 form-password-toggle">
                            <label class="form-label" for="password_confirmation">Confirm Password</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password_confirmation" class="form-control"
                                    name="password_confirmation"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" required />
                                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                            </div>
                        </div>

                        <div class="my-8">
                            <div class="form-check mb-0 ms-2 @error('terms') is-invalid @enderror">
                                <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms" required>
                                <label class="form-check-label" for="terms-conditions">
                                    I agree to
                                    <a href="javascript:void(0);">privacy policy & terms</a>
                                </label>
                            </div>
                            @error('terms')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <button class="btn btn-primary d-grid w-100">
                            Sign up
                        </button>
                    </form>

                    <p class="text-center">
                        <span>Already have an account?</span>
                        <a href="{{url('login')}}">
                            <span>Sign in instead</span>
                        </a>
                    </p>
                </div>
            </div>
            <!-- Register Card -->
        </div>
    </div>
</div>
@endsection