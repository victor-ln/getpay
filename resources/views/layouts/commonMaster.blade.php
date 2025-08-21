<!DOCTYPE html>

<html class="light-style layout-menu-fixed" data-theme="theme-default" data-assets-path="{{ asset('/assets') . '/' }}"
  data-base-url="{{url('/')}}" data-framework="laravel" data-template="vertical-menu-laravel-template-free">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>@yield('title') | GetPay </title>
  <meta name="description"
    content="{{ config('variables.templateDescription') ? config('variables.templateDescription') : '' }}" />
  <meta name="keywords" content="{{ config('variables.templateKeyword') ? config('variables.templateKeyword') : '' }}">

  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('variables.productPage') ? config('variables.productPage') : '' }}">
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />


  <!-- Include Styles -->
  @include('layouts/sections/styles')

  <!-- Include Scripts for customizer, helper, analytics, config -->
  @include('layouts/sections/scriptsIncludes')

  @livewireStyles
</head>

<body>
  @if(Session::has('toast'))
  <div class="bs-toast toast toast-placement-ex show m-2 {{ Session::get('toast')['type'] }} top-0 end-0 denied"
    role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000"
    data-bs-dismiss="3000">
    <div class="toast-header">
      <i class='bx bx-bell me-2'></i>
      <div class="me-auto fw-medium">{{ Session::get('toast')['title'] }}</div>

      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body">
      {{ Session::get('toast')['message'] }}
    </div>
  </div>
  @endif



  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  <x-transaction-receipt />

  <!-- Include Scripts -->
  @include('layouts/sections/scripts')


  @livewireScripts
</body>

</html>