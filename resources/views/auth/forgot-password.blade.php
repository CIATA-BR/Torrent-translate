@extends('layouts.app')
@section('title', __('portal.forgot_password'))
@section('content')
<h1>{{ __('portal.forgot_password') }}</h1>
<p>{{ __('portal.forgot_password_help') }}</p>

<form method="post" action="{{ route('password.email') }}">
@csrf
<div class="field">
<label for="email">{{ __('portal.email') }}</label>
<input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}">
</div>
<button type="submit">{{ __('portal.send_reset_link') }}</button>
</form>

<p><a href="{{ route('login') }}">{{ __('portal.back_to_login') }}</a></p>
@endsection
