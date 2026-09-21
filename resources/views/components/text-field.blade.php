@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'error' => null,
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'optionalLabel' => null,
    'autocomplete' => null,
    'inputmode' => null,
    'placeholder' => null,
    'maxlength' => null,
    'multiline' => false,
    'rows' => 4,
])

@php
    $id = trim((string) $id);
    $name = trim((string) $name);
    $label = trim((string) $label);
    $type = trim((string) $type);
    $rows = (int) $rows;
    $allowedTypes = ['text', 'email', 'password', 'search', 'tel', 'url'];

    if ($id === '' || $name === '' || $label === '') {
        throw new InvalidArgumentException('id, name e label não podem ser vazios.');
    }
    if (! $multiline && ! in_array($type, $allowedTypes, true)) {
        throw new InvalidArgumentException('type não suportado pelo TextField.');
    }
    if ($rows < 1) {
        throw new InvalidArgumentException('rows deve ser maior que zero.');
    }
    if ($maxlength !== null && (int) $maxlength < 1) {
        throw new InvalidArgumentException('maxlength deve ser maior que zero.');
    }

    $helpId = $help ? "{$id}-help" : null;
    $errorId = $error ? "{$id}-error" : null;
    $describedBy = collect([$helpId, $errorId])->filter()->implode(' ');
@endphp

<div class="ciata-field">
    <label class="ciata-field__label" for="{{ $id }}">
        {{ $label }}
        @if($required)
            <span class="ciata-field__required">({{ __('portal.required') }})</span>
        @elseif($optionalLabel)
            <span class="ciata-field__optional">({{ $optionalLabel }})</span>
        @endif
    </label>

    @if($multiline)
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            class="ciata-field__control"
            @if($required) required @endif
            @if($readonly) readonly @endif
            @if($disabled) disabled @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($inputmode) inputmode="{{ $inputmode }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($error) aria-invalid="true" aria-errormessage="{{ $errorId }}" @endif
            {{ $attributes->except(['class']) }}
        >{{ old($name, $value) }}</textarea>
    @else
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            class="ciata-field__control"
            @if($required) required @endif
            @if($readonly) readonly @endif
            @if($disabled) disabled @endif
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($inputmode) inputmode="{{ $inputmode }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($error) aria-invalid="true" aria-errormessage="{{ $errorId }}" @endif
            {{ $attributes->except(['class']) }}
        >
    @endif

    @if($help)
        <div id="{{ $helpId }}" class="ciata-field__help">{{ $help }}</div>
    @endif

    @if($error)
        <div id="{{ $errorId }}" class="ciata-field__error">{{ $error }}</div>
    @endif
</div>
