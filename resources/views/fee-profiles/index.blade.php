 @extends('layouts/contentNavbarLayout')

 @section('title', 'Fee Profiles')

 @section('page-script')
 @vite(['resources/assets/js/ui-toasts.js'])
 @vite(['resources/assets/js/mask.js'])
 @endsection

 @section('content')


 <div class="card">
     <h5 class="card-header">Fee Profile
         <small class="text-muted float-end">
             <a href="{{ route('fee-profiles.create') }}" class="btn btn-primary">
                 <i class="bx bx-plus-circle"></i> &nbsp;&nbsp;
                 Add Profile
             </a>
         </small>
     </h5>

     <div class="card-body">
         <table class="table">
             <thead>
                 <tr>
                     <th>Name</th>
                     <th>Associated Account</th>
                     <th>Calculation Type</th>
                     <th>Actions</th>
                 </tr>
             </thead>
             <tbody>
                 @foreach($profiles as $profile)
                 <tr>
                     <td>{{ $profile->name }}</td>
                     <td>{{ $profile->account->name ?? 'Global' }}</td>
                     <td>{{ $profile->calculation_type }}</td>
                     <td>
                         <a href="{{ route('fee-profiles.edit', $profile->id) }}" class="btn btn-sm btn-warning">Edit</a>
                         {{-- Formulário de Delete --}}
                         <form action="{{ route('fee-profiles.destroy', $profile->id) }}" method="POST" style="display:inline;">
                             @csrf
                             @method('DELETE')
                             <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Remove</button>
                         </form>
                     </td>
                 </tr>
                 @endforeach
             </tbody>
         </table>
     </div>
 </div>
 @endsection