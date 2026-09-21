@extends('layouts.app')
@section('title','Concluir cadastro')
@section('content')
<h1>Concluir cadastro</h1>

@if($referenceDataMissing)
<div class="alert error" role="alert">
<p>Os dados de países e idiomas ainda não foram carregados. O cadastro não pode ser concluído neste momento.</p>
</div>
@else
<form method="post" action="{{ route('register.complete.store',['token'=>$token]) }}">
@csrf
<div class="field"><label for="email">E-mail confirmado</label><input id="email" type="email" value="{{ $email }}" readonly></div>
<div class="field"><label for="full_name">Nome completo</label><input id="full_name" name="full_name" autocomplete="name" required value="{{ old('full_name') }}"></div>
<div class="field"><label for="country_id">País</label><select id="country_id" name="country_id" required><option value="">Selecione</option>@foreach($countries as $country)<option value="{{ $country->id }}" @selected(old('country_id')==$country->id)>{{ $country->name }}</option>@endforeach</select></div>
<div class="field"><label for="language_id">Idioma</label><select id="language_id" name="language_id" required><option value="">Selecione</option>@foreach($languages as $language)<option value="{{ $language->id }}" @selected(old('language_id')==$language->id)>{{ $language->name }}</option>@endforeach</select></div>
<div class="field"><label for="password">Senha</label><input id="password" name="password" type="password" autocomplete="new-password" required minlength="12"></div>
<div class="field"><label for="password_confirmation">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="12"></div>
<button type="submit">Concluir cadastro</button>
</form>
@endif
@endsection
