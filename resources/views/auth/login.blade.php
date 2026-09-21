@extends('layouts.app')
@section('title','Entrar')
@section('content')
<h1>Entrar</h1>
<form method="post" action="{{ route('login.store') }}">
@csrf
<div class="field"><label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"></div>
<div class="field"><label for="password">Senha</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
<div class="field"><label><input type="checkbox" name="remember" value="1"> Manter sessão iniciada</label></div>
<button type="submit">Entrar</button>
</form>
<p><a href="{{ route('register.request') }}">Criar conta</a></p>
@endsection
