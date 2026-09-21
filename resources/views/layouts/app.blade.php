<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','Torrent Translate')</title>
<link rel="stylesheet" href="{{ asset('css/ciata.css') }}">
</head>
<body>
<a class="skip-link" href="#conteudo">Ir para o conteúdo principal</a>
<header class="site-header"><div class="container"><strong>Torrent Translate</strong></div></header>
<main id="conteudo" class="container" tabindex="-1">
@if(session('status'))<div class="alert success" role="status" aria-live="polite">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert" tabindex="-1"><strong>Revise os campos abaixo.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
</body>
</html>
