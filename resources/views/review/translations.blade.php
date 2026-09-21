@extends('layouts.app')
@section('title', __('portal.review_title'))
@section('content')
<h1>{{ __('portal.review_title') }}</h1>

<p>{{ __('portal.review_help') }}</p>

@forelse($translations as $translation)
<article class="translation-card">
<h2>{{ __('portal.translation') }} {{ $translation->source->id }} — {{ $translation->locale->name }} ({{ $translation->locale->code }})</h2>

@if($translation->updater)
<p>{{ __('portal.review_submitted_by', ['name' => $translation->updater->full_name ?: $translation->updater->email]) }}</p>
@endif

<div class="field">
<label for="source-{{ $translation->id }}">{{ __('portal.original_text') }}</label>
<textarea id="source-{{ $translation->id }}" rows="4" readonly lang="en-US">{{ $translation->source->msgid }}</textarea>
</div>

<form method="post" action="{{ route('review.translations.update', $translation) }}">
@csrf
<div class="field">
<label for="review-text-{{ $translation->id }}">{{ __('portal.translation') }}</label>
<textarea id="review-text-{{ $translation->id }}" name="text" rows="4" required>{{ old('text', $translation->text) }}</textarea>
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
