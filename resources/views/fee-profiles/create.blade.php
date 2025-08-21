 @extends('layouts/contentNavbarLayout')

 @section('title', 'Fee Profiles')

 @section('page-script')
 @vite(['resources/assets/js/ui-toasts.js'])
 @vite(['resources/assets/js/fee-profile-select.js'])
 @endsection

 @section('content')
 <div class="card">
     <div class="card-header d-flex justify-content-between align-items-center">
         <h5 class="mb-0">Create New Rate Profile</h5>
     </div>
     </h5>

     <div class="card-body">
         @livewire('fee-profile-form')
     </div>
 </div>
 @endsection