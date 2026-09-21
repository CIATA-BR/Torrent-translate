@extends('layouts.app')
@section('title','Criar conta')
@section('content')
@if(session('registration_email_sent'))
<h1>Confira seu e-mail</h1>
<div class="alert success" role="status" aria-live="polite">
    <p>Enviamos um link de confirmação para <strong>{{ session('registration_email') }}</strong>.</p>
    <p>O link expira em 30 minutos.</p>
</div>
<p><a href="{{ url('/') }}">Voltar para o início</a></p>
@else
<h1>Criar conta</h1>
<p>Informe seu e-mail. Enviaremos um link seguro para concluir o cadastro.</p>
<form method="post" action="{{ route('register.send') }}">
@csrf
<div class="field">
<label for="email">E-mail</label>
<input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}">
</div>
<button type="submit">Enviar link de confirmação</button>
</form>
@endif
@endsection
