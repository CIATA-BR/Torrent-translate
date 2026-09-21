@extends('layouts.app')
@section('title', __('portal.login'))
@section('content')
<h1>{{ __('portal.login') }}</h1>
<form method="post" action="{{ route('login.store') }}">
@csrf
<x-text-field
    id="email"
    name="email"
    type="email"
    :label="__('portal.email')"
    autocomplete="email"
    :required="true"
    :error="$errors->first('email') ?: null"
/>
<x-password-field
    id="password"
    name="password"
    :label="__('portal.password')"
    autocomplete="current-password"
    :error="$errors->first('password') ?: null"
/>
<div class="field"><label><input type="checkbox" name="remember" value="1"> {{ __('portal.remember') }}</label></div>
<button type="submit">{{ __('portal.login') }}</button>
</form>
<p><a href="{{ route('password.request') }}">{{ __('portal.forgot_password') }}</a></p>
<p><a href="{{ route('register.request') }}">{{ __('portal.create_account') }}</a></p>
@endsection
