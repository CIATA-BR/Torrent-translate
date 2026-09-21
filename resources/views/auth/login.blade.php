@extends('layouts.app')
@section('title', __('portal.login'))
@section('content')
<h1>{{ __('portal.login') }}</h1>
<form method="post" action="{{ route('login.store') }}">
@csrf
<div class="field"><label for="email">{{ __('portal.email') }}</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"></div>
<div class="field"><label for="password">{{ __('portal.password') }}</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
<div class="field"><label><input type="checkbox" name="remember" value="1"> {{ __('portal.remember') }}</label></div>
<button type="submit">{{ __('portal.login') }}</button>
</form>
<p><a href="{{ route('password.request') }}">{{ __('portal.forgot_password') }}</a></p>
<p><a href="{{ route('register.request') }}">{{ __('portal.create_account') }}</a></p>
@endsection
