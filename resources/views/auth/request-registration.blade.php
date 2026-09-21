@extends('layouts.app')
@section('title', __('portal.create_account'))
@section('content')
@if(session('registration_email_sent'))
<h1>{{ __('portal.registration_sent_title') }}</h1>
<div class="alert success" role="status" aria-live="polite">
<p>{{ __('portal.registration_sent', ['email' => session('registration_email')]) }}</p>
<p>{{ __('portal.registration_expires') }}</p>
</div>
<p><a href="{{ url('/') }}">{{ __('portal.back_home') }}</a></p>
@else
<h1>{{ __('portal.create_account') }}</h1>
<p>{{ __('portal.registration_request_help') }}</p>
<form method="post" action="{{ route('register.send') }}">
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
<button type="submit">{{ __('portal.registration_send_link') }}</button>
</form>
@endif
@endsection
