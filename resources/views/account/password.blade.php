@extends(auth()->user()->hasRole('mahasiswa') ? 'layouts.mahasiswa' : 'layouts.app', ['title' => __('auth.ubah_password_title')])

@section('content')
    <div class="row">
        <div class="col-md-6">
            <x-ui.card :title="__('auth.ubah_password_title')">
                <p class="text-body-secondary">{{ __('auth.ubah_password_intro') }}</p>
                @include('partials.ubah-password-form')
            </x-ui.card>
        </div>
    </div>
@endsection
