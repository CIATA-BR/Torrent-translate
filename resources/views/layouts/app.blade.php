<!doctype html>
<html lang="{{ app()->getLocale() === 'en' ? 'en-US' : 'pt-BR' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', __('portal.brand'))</title>
<link rel="stylesheet" href="{{ asset('css/ciata.css') }}">
</head>
<body>
<a class="skip-link" href="#conteudo">{{ __('portal.skip') }}</a>
<header class="site-header">
<div class="container">
<strong>{{ __('portal.brand') }}</strong>
<nav aria-label="{{ __('portal.primary_navigation') }}">
<a href="{{ request()->fullUrlWithQuery(['site_lang' => 'pt-BR']) }}" lang="pt-BR">{{ __('portal.portuguese') }}</a>
<span aria-hidden="true"> | </span>
<a href="{{ request()->fullUrlWithQuery(['site_lang' => 'en-US']) }}" lang="en">{{ __('portal.english') }}</a>
@if(auth()->check() && auth()->user()->isPortalReviewer())
<span aria-hidden="true"> | </span>
<a href="{{ route('review.translations.index') }}">{{ __('portal.review_link') }}</a>
@endif
@if(auth()->check() && auth()->user()->isPortalAdmin())
<span aria-hidden="true"> | </span>
<a href="{{ route('admin.translations.index') }}">{{ __('portal.admin_link') }}</a>
<span aria-hidden="true"> | </span>
<a href="{{ route('admin.audit.index') }}">{{ __('portal.audit_link') }}</a>
@endif
@if(auth()->check())
<span aria-hidden="true"> | </span>
<form method="post" action="{{ route('logout') }}" style="display:inline">
@csrf
<button type="submit">{{ __('portal.logout') }}</button>
</form>
@endif
</nav>
</div>
</header>
<main id="conteudo" class="container" tabindex="-1">
@if(session('status'))
<div id="flash-status" class="alert success" role="status" aria-live="polite" aria-atomic="true" tabindex="-1">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="alert error" role="alert" tabindex="-1">
<strong>{{ __('portal.review_errors') }}</strong>
<ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif
@yield('content')
</main>
@if(session('status'))
<script>
document.getElementById('flash-status')?.focus();
</script>
@endif
</body>
</html>
