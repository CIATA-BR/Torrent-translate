@extends('layouts.app')
@section('title', __('portal.audit_title'))
@section('content')
<h1>{{ __('portal.audit_title') }}</h1>

<p>{{ __('portal.audit_help') }}</p>

<form method="get" class="filters">
<x-text-field
    id="event"
    name="event"
    type="search"
    :label="__('portal.audit_event_filter')"
    :value="$eventFilter"
    placeholder="translation.approved"
/>
<button type="submit">{{ __('portal.apply_filters') }}</button>
</form>

<section aria-labelledby="events-heading">
<h2 id="events-heading">{{ __('portal.audit_events') }}</h2>
<div class="ciata-table-wrap" tabindex="0" role="region" aria-label="{{ __('portal.audit_events_table_label') }}">
<table class="ciata-table">
<thead>
<tr>
<th scope="col">{{ __('portal.audit_when') }}</th>
<th scope="col">{{ __('portal.audit_event') }}</th>
<th scope="col">{{ __('portal.audit_actor') }}</th>
<th scope="col">{{ __('portal.audit_subject') }}</th>
<th scope="col">{{ __('portal.audit_details') }}</th>
</tr>
</thead>
<tbody>
@forelse($events as $event)
<tr>
<td>{{ $event->created_at?->format('Y-m-d H:i:s') }}</td>
<td><code>{{ $event->event }}</code></td>
<td>{{ $event->user?->full_name ?: $event->user?->email ?: __('portal.audit_system') }}</td>
<td>
@if($event->subject_type)
{{ class_basename($event->subject_type) }} #{{ $event->subject_id }}
@else
—
@endif
</td>
<td>
@if(is_array($event->metadata) && $event->metadata !== [])
<details>
<summary>{{ __('portal.audit_open_details') }}</summary>
<dl>
@foreach($event->metadata as $key => $value)
<dt>{{ $key }}</dt>
<dd>{{ is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</dd>
@endforeach
</dl>
</details>
@else
—
@endif
</td>
</tr>
@empty
<tr><td colspan="5">{{ __('portal.audit_none') }}</td></tr>
@endforelse
</tbody>
</table>
</div>
{{ $events->links() }}
</section>

<section aria-labelledby="history-heading">
<h2 id="history-heading">{{ __('portal.history_title') }}</h2>
<div class="ciata-table-wrap" tabindex="0" role="region" aria-label="{{ __('portal.history_table_label') }}">
<table class="ciata-table">
<thead>
<tr>
<th scope="col">{{ __('portal.audit_when') }}</th>
<th scope="col">{{ __('portal.audit_actor') }}</th>
<th scope="col">{{ __('portal.admin_locale') }}</th>
<th scope="col">{{ __('portal.original_text') }}</th>
<th scope="col">{{ __('portal.history_status') }}</th>
<th scope="col">{{ __('portal.history_previous_text') }}</th>
<th scope="col">{{ __('portal.history_new_text') }}</th>
</tr>
</thead>
<tbody>
@forelse($revisions as $revision)
<tr>
<td>{{ $revision->created_at?->format('Y-m-d H:i:s') }}</td>
<td>{{ $revision->user?->full_name ?: $revision->user?->email ?: __('portal.audit_system') }}</td>
<td>{{ $revision->translation?->locale?->code ?: '—' }}</td>
<td>
<details>
<summary>{{ __('portal.audit_open_text') }}</summary>
<p lang="en-US">{{ $revision->translation?->source?->msgid }}</p>
</details>
</td>
<td>{{ $revision->old_status ?: '—' }} → {{ $revision->new_status ?: '—' }}</td>
<td>
<details>
<summary>{{ __('portal.audit_open_text') }}</summary>
<p>{{ $revision->old_text }}</p>
</details>
</td>
<td>
<details>
<summary>{{ __('portal.audit_open_text') }}</summary>
<p>{{ $revision->new_text }}</p>
</details>
</td>
</tr>
@empty
<tr><td colspan="7">{{ __('portal.history_none') }}</td></tr>
@endforelse
</tbody>
</table>
</div>
{{ $revisions->links() }}
</section>
@endsection
