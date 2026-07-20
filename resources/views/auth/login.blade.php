@extends('layouts.auth', ['title' => __('auth.login_title')])

@section('content')
    <p class="login-box-msg">
        {{ __('auth.login_title') }} — {{ $namaUniversitas ?? config('app.name') }}
    </p>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <x-form.input name="email" type="email" :label="__('auth.email')" :value="old('email')" required autofocus />
        <x-form.input name="password" type="password" :label="__('auth.password_label')" required />

        <div class="row">
            <div class="col-8">
                <div class="form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">{{ __('auth.remember') }}</label>
                </div>
            </div>
            <div class="col-4">
                <x-ui.button type="submit" variant="primary" class="w-100">{{ __('auth.submit') }}</x-ui.button>
            </div>
        </div>
    </form>

    <p class="mt-3 mb-0">
        <a href="{{ route('password.request') }}">{{ __('auth.forgot_password') }}</a>
    </p>
@endsection
