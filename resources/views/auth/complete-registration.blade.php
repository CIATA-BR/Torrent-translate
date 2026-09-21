@extends('layouts.app')
@section('title', __('portal.complete_registration'))
@section('content')
<h1>{{ __('portal.complete_registration') }}</h1>

@if($referenceDataMissing)
<div class="alert error" role="alert">
<p>{{ __('portal.registration_reference_missing') }}</p>
</div>
@else
<p id="registration-password-requirements">{{ __('portal.password_requirements') }}</p>
<form method="post" action="{{ route('register.complete.store',['token'=>$token]) }}">
@csrf
<x-text-field
    id="email"
    name="confirmed_email"
    type="email"
    :label="__('portal.confirmed_email')"
    :value="$email"
    :readonly="true"
/>
<x-text-field
    id="full_name"
    name="full_name"
    :label="__('portal.full_name')"
    autocomplete="name"
    :required="true"
    :error="$errors->first('full_name') ?: null"
/>
<div class="ciata-field">
<label class="ciata-field__label" for="country_id">{{ __('portal.country') }} <span class="ciata-field__required">({{ __('portal.required') }})</span></label>
<select id="country_id" name="country_id" required @if($errors->has('country_id')) aria-invalid="true" aria-errormessage="country_id-error" @endif>
<option value="">{{ __('portal.select_option') }}</option>
@foreach($countries as $country)<option value="{{ $country->id }}" @selected(old('country_id')==$country->id)>{{ $country->name }}</option>@endforeach
</select>
@error('country_id')<div id="country_id-error" class="ciata-field__error">{{ $message }}</div>@enderror
</div>
<div class="ciata-field">
<label class="ciata-field__label" for="language_id">{{ __('portal.language') }} <span class="ciata-field__required">({{ __('portal.required') }})</span></label>
<select id="language_id" name="language_id" required @if($errors->has('language_id')) aria-invalid="true" aria-errormessage="language_id-error" @endif>
<option value="">{{ __('portal.select_option') }}</option>
@foreach($languages as $language)<option value="{{ $language->id }}" @selected(old('language_id')==$language->id)>{{ $language->name }}</option>@endforeach
</select>
@error('language_id')<div id="language_id-error" class="ciata-field__error">{{ $message }}</div>@enderror
</div>
<x-password-field
    id="password"
    name="password"
    :label="__('portal.password')"
    autocomplete="new-password"
    :help="__('portal.password_requirements')"
    :error="$errors->first('password') ?: null"
    :minlength="12"
/>
<x-password-field
    id="password_confirmation"
    name="password_confirmation"
    :label="__('portal.confirm_password')"
    autocomplete="new-password"
    :error="$errors->first('password_confirmation') ?: null"
    :minlength="12"
/>
<button type="submit">{{ __('portal.complete_registration') }}</button>
</form>
@endif
@endsection
