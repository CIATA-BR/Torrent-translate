@props([
    'id',
    'name',
    'label',
    'autocomplete' => 'current-password',
    'help' => null,
    'error' => null,
    'required' => true,
    'minlength' => null,
])

@php
    $helpId = $help ? "{$id}-help" : null;
    $errorId = $error ? "{$id}-error" : null;
    $describedBy = collect([$helpId, $errorId])->filter()->implode(' ');
@endphp

<div class="ciata-field ciata-password-field" data-password-field>
    <label class="ciata-field__label" for="{{ $id }}">
        {{ $label }}
        @if($required)
            <span class="ciata-field__required">({{ __('portal.required') }})</span>
        @endif
    </label>

    <div class="ciata-password-field__row">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            class="ciata-field__control"
            autocomplete="{{ $autocomplete }}"
            @if($required) required @endif
            @if($minlength) minlength="{{ $minlength }}" @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($error) aria-invalid="true" aria-errormessage="{{ $errorId }}" @endif
            {{ $attributes->except(['class']) }}
        >
        <button
            type="button"
            class="ciata-password-toggle"
            data-password-toggle
            aria-controls="{{ $id }}"
            aria-pressed="false"
        >{{ __('portal.show_password') }}</button>
    </div>

    @if($help)
        <div id="{{ $helpId }}" class="ciata-field__help">{{ $help }}</div>
    @endif

    @if($error)
        <div id="{{ $errorId }}" class="ciata-field__error">{{ $error }}</div>
    @endif
</div>
