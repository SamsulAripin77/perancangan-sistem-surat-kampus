@if (session('success'))
    <span class="js-flash" data-type="success" data-msg="{{ session('success') }}"></span>
@endif
@if (session('error'))
    <span class="js-flash" data-type="error" data-msg="{{ session('error') }}"></span>
@endif
