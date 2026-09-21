@extends('layouts.app')
@section('title', __('portal.admin_title'))
@section('content')
<h1>{{ __('portal.admin_title') }}</h1>

<p><a href="{{ route('translations.index') }}">{{ __('portal.back_to_translations') }}</a></p>

<section aria-labelledby="catalog-heading">
<h2 id="catalog-heading">{{ __('portal.admin_catalog') }}</h2>
<p>{{ __('portal.admin_source_ref', ['ref' => $sourceRef]) }}</p>
<p>{{ __('portal.admin_publish_base', ['base' => $publishBase]) }}</p>

@if($githubConfigured)
<form method="post" action="{{ route('admin.translations.sync') }}">
@csrf
<button type="submit">{{ __('portal.admin_sync') }}</button>
</form>
@else
<div class="alert error" role="status">
{{ __('portal.admin_github_not_configured') }}
</div>
@endif

@if($githubStatusError)
<div class="alert error" role="status">
{{ __('portal.admin_pr_status_failed') }}
</div>
@endif
</section>

@if(session('published_pr_url'))
<p>
<a href="{{ session('published_pr_url') }}" target="_blank" rel="noopener">
{{ __('portal.admin_open_pr_number', ['number' => session('published_pr_number')]) }}
</a>
</p>
@endif

<section aria-labelledby="languages-heading">
<h2 id="languages-heading">{{ __('portal.admin_languages') }}</h2>

<table>
<thead>
<tr>
<th scope="col">{{ __('portal.admin_locale') }}</th>
<th scope="col">{{ __('portal.admin_progress') }}</th>
<th scope="col">{{ __('portal.admin_invalid') }}</th>
<th scope="col">{{ __('portal.admin_pr_status') }}</th>
<th scope="col">{{ __('portal.admin_action') }}</th>
</tr>
</thead>
<tbody>
@foreach($locales as $row)
<tr>
<th scope="row">{{ $row['locale']->name }} ({{ $row['locale']->code }})</th>
<td>{{ $row['translated'] }} / {{ $row['total'] }} — {{ $row['percent'] }}%</td>
<td>{{ $row['invalid'] }}</td>
<td>
@if($row['pull_request'])
<a href="{{ $row['pull_request']['url'] }}" target="_blank" rel="noopener">
{{ __('portal.admin_pr_open', ['number' => $row['pull_request']['number']]) }}
</a>
<br>
<small>{{ __('portal.admin_pr_branch', ['branch' => $row['pull_request']['branch']]) }}</small>
@else
{{ __('portal.admin_pr_none') }}
@endif
</td>
<td>
@if($row['publishable'] && $githubConfigured)
<form method="post" action="{{ route('admin.translations.publish') }}">
@csrf
<input type="hidden" name="locale_id" value="{{ $row['locale']->id }}">
@if($row['pull_request'])
<button type="submit">
{{ __('portal.admin_update_pr', ['number' => $row['pull_request']['number']]) }}
</button>
@else
<button type="submit">{{ __('portal.admin_publish', ['locale' => $row['locale']->code]) }}</button>
@endif
</form>
@elseif(!$row['publishable'])
{{ __('portal.admin_not_ready') }}
@else
{{ __('portal.admin_token_required') }}
@endif
</td>
</tr>
@endforeach
</tbody>
</table>
</section>
@endsection
