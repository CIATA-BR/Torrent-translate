@extends('layouts.app')
@section('title', __('portal.translations'))
@section('content')
<h1>{{ __('portal.translations') }}</h1>

<form method="get" class="filters">
<div class="field">
<label for="source_lang">{{ __('portal.source_language') }}</label>
<select id="source_lang" name="source_lang">
<option value="en-US" @selected($sourceLanguage === 'en-US')>{{ __('portal.source_english') }}</option>
<option value="pt-BR" @selected($sourceLanguage === 'pt-BR')>{{ __('portal.source_portuguese') }}</option>
</select>
</div>

<div class="field">
<label for="target_locale">{{ __('portal.target_language') }}</label>
<select id="target_locale" name="target_locale">
@foreach($locales as $locale)
<option value="{{ $locale->id }}" @selected($targetLocale->id === $locale->id)>{{ $locale->name }} ({{ $locale->code }})</option>
@endforeach
</select>
</div>

<div class="field">
<label for="q">{{ __('portal.search_original') }}</label>
<input id="q" name="q" type="search" value="{{ $search }}">
</div>

<div class="field">
<label for="filter">{{ __('portal.filter') }}</label>
<select id="filter" name="filter">
<option value="">{{ __('portal.all') }}</option>
<option value="untranslated" @selected($filter === 'untranslated')>{{ __('portal.untranslated') }}</option>
</select>
</div>

<button type="submit">{{ __('portal.apply_filters') }}</button>
</form>

<p id="translation-progress" role="status" aria-live="polite" aria-atomic="true">
{{ __('portal.progress_with_validation', ['translated' => $translated, 'total' => $total, 'percent' => $percent, 'invalid' => $invalid]) }}
</p>

@if($sourceLanguage === 'pt-BR' && $translated < $total)
<p>{{ __('portal.portuguese_source_help') }}</p>
@endif

@if($publishable)
<p><a class="button-link" href="{{ route('translations.export', ['target_locale' => $targetLocale->id, 'source_lang' => $sourceLanguage]) }}">{{ __('portal.generate') }} — {{ $targetLocale->code }}.po</a></p>
@else
<p>{{ __('portal.generate_help_validated') }}</p>
@endif

@forelse($sources as $source)
@php
$targetTranslation = $source->translations->firstWhere('locale_id', $targetLocale->id);
$ptTranslation = $ptBr ? $source->translations->firstWhere('locale_id', $ptBr->id) : null;
$sourceText = $sourceLanguage === 'pt-BR' ? $ptTranslation?->text : $source->msgid;
$locked = filled($targetTranslation?->text) && $editId !== $source->id;
@endphp
<article class="translation-card">
<h2>{{ __('portal.translation') }} {{ $source->id }}</h2>

<div class="field">
<label for="source-{{ $source->id }}">{{ __('portal.original_text') }}</label>
<textarea id="source-{{ $source->id }}" rows="4" readonly lang="{{ $sourceLanguage === 'pt-BR' ? 'pt-BR' : 'en-US' }}">{{ $sourceText }}</textarea>
</div>

<form method="post" action="{{ route('translations.store', $source) }}" class="translation-form">
@csrf
<input type="hidden" name="target_locale" value="{{ $targetLocale->id }}">
<input type="hidden" name="source_lang" value="{{ $sourceLanguage }}">
@if($filter !== '')
<input type="hidden" name="filter" value="{{ $filter }}">
@endif
@if($search !== '')
<input type="hidden" name="q" value="{{ $search }}">
@endif
@if(request()->integer('page') > 1)
<input type="hidden" name="page" value="{{ request()->integer('page') }}">
@endif

<div class="field">
<label for="text-{{ $source->id }}">{{ __('portal.translation') }}</label>
<textarea id="text-{{ $source->id }}" name="text" rows="4" @readonly($locked) required>{{ old('text', $targetTranslation?->text) }}</textarea>
</div>

@if($locked)
<a href="{{ request()->fullUrlWithQuery(['edit' => $source->id]) }}">{{ __('portal.change') }}</a>
@else
<button type="submit" class="save-translation" @disabled(blank(old('text', $targetTranslation?->text)))>{{ __('portal.save') }}</button>
@if(filled($targetTranslation?->text))
<a href="{{ request()->fullUrlWithQuery(['edit' => null]) }}">{{ __('portal.cancel_change') }}</a>
@endif
@endif
</form>
</article>
@empty
<p>{{ __('portal.no_entries') }}</p>
@endforelse

{{ $sources->links() }}

<script>
document.querySelectorAll('.translation-form').forEach((form) => {
    const textarea = form.querySelector('textarea[name="text"]');
    const button = form.querySelector('.save-translation');

    if (!textarea || !button) {
        return;
    }

    const syncButton = () => {
        button.disabled = textarea.value.trim() === '';
    };

    textarea.addEventListener('input', syncButton);
    syncButton();
});
</script>
@endsection
