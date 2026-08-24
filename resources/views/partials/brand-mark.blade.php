@php($brandLogoPath = public_path('images/polibest-logo.png'))

@if(file_exists($brandLogoPath))
    <span class="brand-mark brand-mark-logo" aria-hidden="true">
        <img src="{{ asset('images/polibest-logo.png') }}" alt="">
    </span>
@else
    <span class="brand-mark" aria-hidden="true">PS</span>
@endif
