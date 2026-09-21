@extends('layouts.app')
@section('title', __('portal.reset_password'))
@section('content')
<h1>{{ __('portal.reset_password') }}</h1>

<p>{{ __('portal.reset_password_for', ['email' => $email]) }}</p>

<form method="post" action="{{ route('password.update', ['token' => $token]) }}">
@csrf
<div class="field">
<label for="password">{{ __('portal.new_password') }}</label>
<input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required>
</div>
<div class="field">
<label for="password_confirmation">{{ __('portal.confirm_password') }}</label>
<input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>
</div>
<button type="submit">{{ __('portal.reset_password') }}</button>
</form>
@endsection
