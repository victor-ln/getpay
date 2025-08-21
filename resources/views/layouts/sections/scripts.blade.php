<!-- BEGIN: Vendor JS-->

@vite([
'resources/assets/vendor/libs/jquery/jquery.js',
'resources/assets/vendor/libs/popper/popper.js',
'resources/assets/vendor/js/bootstrap.js',
'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
'resources/assets/vendor/js/menu.js'
])

@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
@vite(['resources/assets/js/main.js'])
@vite(['resources/assets/js/transaction-receipt.js'])
<script>
    window.gtranslateSettings = {
        "default_language": "en",
        "native_language_names": true,
        "languages": ["en", "pt"],
        "wrapper_selector": ".gtranslate_wrapper",
        "switcher_horizontal_position": "left",
        "switcher_vertical_position": "bottom",
        "flag_style": "3d"
    }
</script>
<script src="https://cdn.gtranslate.net/widgets/latest/float.js" defer></script>

<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
@vite('resources/js/app.js')
<!-- END: Page JS-->