@extends('layouts.app')
@section('title', __('portal.forgot_password'))
@section('content')
@if(session('password_reset_email_sent'))
<h1>{{ __('portal.password_reset_sent_title') }}</h1>
<div class="alert success" role="status" aria-live="polite">
<p>{{ __('portal.password_reset_link_sent') }}</p>
<p>{{ __('portal.password_reset_link_expires') }}</p>
</div>
<p><a href="{{ route('login') }}">{{ __('portal.back_to_login') }}</a></p>
@else
<h1>{{ __('portal.forgot_password') }}</h1>
<p id="forgot-password-help">{{ __('portal.forgot_password_help') }}</p>

<form method="post" action="{{ route('password.email') }}">
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
<button type="submit">{{ __('portal.send_reset_link') }}</button>
</form>

<p><a href="{{ route('login') }}">{{ __('portal.back_to_login') }}</a></p>
@endif
@endsection
