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
<p>Informe seu e-mail. Enviaremos um link seguro para concluir o cadastro.</p>
<form method="post" action="{{ route('register.send') }}">
@csrf
<div class="field"><label for="email">{{ __('portal.email') }}</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"></div>
<button type="submit">Enviar link de confirmação</button>
</form>
@endif
@endsection
