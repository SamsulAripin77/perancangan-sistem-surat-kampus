@extends('layouts.auth', ['title' => __('auth.force_change_title')])

@section('content')
    <p class="login-box-msg">{{ __('auth.force_change_intro') }}</p>

    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf

        <x-form.input name="password" type="password" :label="__('auth.password_new')" required autofocus />
        <x-form.input name="password_confirmation" type="password" :label="__('auth.password_confirmation')" required />

        <x-ui.button type="submit" variant="primary" class="w-100">{{ __('auth.force_change_submit') }}</x-ui.button>
    </form>
@endsection
