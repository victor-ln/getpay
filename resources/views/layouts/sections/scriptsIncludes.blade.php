@php
use Illuminate\Support\Facades\Vite;
@endphp
<!-- laravel style -->
@vite(['resources/assets/vendor/js/helpers.js'])

<!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
@vite(['resources/assets/js/config.js'])

<!-- Place this tag in your head or just before your close body tag. -->
<script async defer src="https://buttons.github.io/buttons.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    var toastElList = [].slice.call(document.querySelectorAll('.toast.denied'))
    toastElList.forEach(function(toastEl) {
      var toast = bootstrap.Toast.getOrCreateInstance(toastEl, {
        autohide: true,
        delay: 3000
      });
      toast.show();
    });
  });
</script>