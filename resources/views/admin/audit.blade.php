@extends('layouts.app')
@section('title', __('portal.audit_title'))
@section('content')
<h1>{{ __('portal.audit_title') }}</h1>

<p>{{ __('portal.audit_help') }}</p>

<form method="get" class="filters">
<div class="field">
<label for="event">{{ __('portal.audit_event_filter') }}</label>
<input id="event" name="event" type="search" value="{{ $eventFilter }}" placeholder="translation.approved">
</div>
<button type="submit">{{ __('portal.apply_filters') }}</button>
</form>

<section aria-labelledby="events-heading">
<h2 id="events-heading">{{ __('portal.audit_events') }}</h2>
@forelse($events as $event)
<article class="translation-card">
<h3>{{ $event->event }}</h3>
<p><strong>{{ __('portal.audit_when') }}:</strong> {{ $event->created_at?->format('Y-m-d H:i:s') }}</p>
<p><strong>{{ __('portal.audit_actor') }}:</strong> {{ $event->user?->full_name ?: $event->user?->email ?: __('portal.audit_system') }}</p>
@if($event->subject_type)
<p><strong>{{ __('portal.audit_subject') }}:</strong> {{ class_basename($event->subject_type) }} #{{ $event->subject_id }}</p>
@endif
@if(is_array($event->metadata) && $event->metadata !== [])
<details>
<summary>{{ __('portal.audit_details') }}</summary>
<dl>
@foreach($event->metadata as $key => $value)
<dt>{{ $key }}</dt>
<dd>{{ is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd>
@endforeach
</dl>
</details>
@endif
</article>
@empty
<p>{{ __('portal.audit_none') }}</p>
@endforelse
{{ $events->links() }}
</section>

<section aria-labelledby="history-heading">
<h2 id="history-heading">{{ __('portal.history_title') }}</h2>
@forelse($revisions as $revision)
<article class="translation-card">
<h3>
{{ __('portal.translation') }}
{{ $revision->translation?->source?->id }}
@if($revision->translation?->locale)
— {{ $revision->translation->locale->name }} ({{ $revision->translation->locale->code }})
@endif
</h3>
<p><strong>{{ __('portal.audit_when') }}:</strong> {{ $revision->created_at?->format('Y-m-d H:i:s') }}</p>
<p><strong>{{ __('portal.audit_actor') }}:</strong> {{ $revision->user?->full_name ?: $revision->user?->email ?: __('portal.audit_system') }}</p>
<p><strong>{{ __('portal.history_status') }}:</strong> {{ $revision->old_status ?: '—' }} → {{ $revision->new_status ?: '—' }}</p>

<div class="field">
<label for="history-source-{{ $revision->id }}">{{ __('portal.original_text') }}</label>
<textarea id="history-source-{{ $revision->id }}" rows="3" readonly lang="en-US">{{ $revision->translation?->source?->msgid }}</textarea>
</div>

<div class="field">
<label for="history-old-{{ $revision->id }}">{{ __('portal.history_previous_text') }}</label>
<textarea id="history-old-{{ $revision->id }}" rows="3" readonly>{{ $revision->old_text }}</textarea>
</div>

<div class="field">
<label for="history-new-{{ $revision->id }}">{{ __('portal.history_new_text') }}</label>
<textarea id="history-new-{{ $revision->id }}" rows="3" readonly>{{ $revision->new_text }}</textarea>
</div>
</article>
@empty
<p>{{ __('portal.history_none') }}</p>
@endforelse
{{ $revisions->links() }}
</section>
@endsection
