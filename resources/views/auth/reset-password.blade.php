@extends('layouts.app')
@section('title', __('portal.reset_password'))
@section('content')
<h1>{{ __('portal.reset_password') }}</h1>

<p>{{ __('portal.reset_password_for', ['email' => $email]) }}</p>
<p id="password-requirements">{{ __('portal.password_requirements') }}</p>

<form method="post" action="{{ route('password.update', ['token' => $token]) }}">
@csrf
<x-password-field
    id="password"
    name="password"
    :label="__('portal.new_password')"
    autocomplete="new-password"
    :help="__('portal.password_requirements')"
    :error="$errors->first('password') ?: null"
    :minlength="12"
/>
<x-password-field
    id="password_confirmation"
    name="password_confirmation"
    :label="__('portal.confirm_password')"
    autocomplete="new-password"
    :error="$errors->first('password_confirmation') ?: null"
    :minlength="12"
/>
<button type="submit">{{ __('portal.reset_password') }}</button>
</form>
@endsection
