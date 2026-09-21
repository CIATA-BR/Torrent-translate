@extends('layouts.app')
@section('title','Criar conta')
@section('content')
<h1>Criar conta</h1>
<p>Informe seu e-mail. Enviaremos um link seguro para concluir o cadastro.</p>
<form method="post" action="{{ route('register.send') }}">@csrf
<div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"></div>
<button type="submit">Enviar link de confirmação</button>
</form>
@endsection
