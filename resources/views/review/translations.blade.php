@extends('layouts.app')
@section('title', __('portal.review_title'))
@section('content')
<h1>{{ __('portal.review_title') }}</h1>

<p>{{ __('portal.review_help') }}</p>

@forelse($translations as $translation)
<article class="translation-card">
<h2>{{ __('portal.translation') }} {{ $translation->source->id }} — {{ $translation->locale->name }} ({{ $translation->locale->code }})</h2>

@php
$submittedTranslationId = (int) old('translation_id');
$reviewValue = $submittedTranslationId === $translation->id
    ? old('text', $translation->text)
    : $translation->text;
$reviewError = $submittedTranslationId === $translation->id ? $errors->first('text') : null;
@endphp

@if($translation->updater)
<p>{{ __('portal.review_submitted_by', ['name' => $translation->updater->full_name ?: $translation->updater->email]) }}</p>
@endif

<div class="field">
<label for="source-{{ $translation->id }}">{{ __('portal.original_text') }}</label>
<textarea id="source-{{ $translation->id }}" rows="4" readonly lang="en-US">{{ $translation->source->msgid }}</textarea>
</div>

<form method="post" action="{{ route('review.translations.update', $translation) }}">
@csrf
<input type="hidden" name="translation_id" value="{{ $translation->id }}">
<input type="hidden" name="version" value="{{ $translation->updated_at?->toISOString() }}">
<div class="ciata-field">
<label class="ciata-field__label" for="review-text-{{ $translation->id }}">{{ __('portal.translation') }} <span class="ciata-field__required">({{ __('portal.required') }})</span></label>
<textarea
    id="review-text-{{ $translation->id }}"
    name="text"
    rows="4"
    required
    @if($reviewError) aria-invalid="true" aria-errormessage="review-text-{{ $translation->id }}-error" @endif
>{{ $reviewValue }}</textarea>
@if($reviewError)
<div id="review-text-{{ $translation->id }}-error" class="ciata-field__error">{{ $reviewError }}</div>
@endif
</div>

<button type="submit" name="action" value="approve">{{ __('portal.review_approve') }}</button>
<button type="submit" name="action" value="reject">{{ __('portal.review_reject') }}</button>
</form>
</article>
@empty
<p>{{ __('portal.review_none') }}</p>
@endforelse

{{ $translations->links() }}
@endsection
