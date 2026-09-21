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

<footer class="ciata-footer" role="contentinfo">
<div class="ciata-footer__container">
<section class="ciata-footer__column" aria-labelledby="footer-ciata">
<h2 id="footer-ciata">CIATA</h2>
<p>Centro de Inclusão Através da Tecnologia Assistiva</p>
<p>Inclusão, acessibilidade e transformação social.</p>
<p>CNPJ 13.103.993/0001-79</p>
<p>ciata.org.br · ciata.ong.br · ciata.cloud</p>
</section>

<section class="ciata-footer__column" aria-labelledby="footer-contato">
<h2 id="footer-contato">{{ __('portal.footer_contact') }}</h2>
<ul class="ciata-footer__links">
<li><a href="https://wa.me/551142114411" target="_blank" rel="noopener noreferrer">WhatsApp: (11) 4211-4411<span class="sr-only">, {{ __('portal.opens_new_window') }}</span></a></li>
<li><a href="mailto:contato@ciata.org.br">contato@ciata.org.br</a></li>
</ul>
<form action="https://www.paypal.com/donate" method="post" target="_blank">
<input type="hidden" name="hosted_button_id" value="8ZTCDNG3DBK2J">
<button type="submit">{{ __('portal.footer_support') }}<span class="sr-only">, {{ __('portal.opens_new_window') }}</span></button>
</form>
</section>

<section class="ciata-footer__column" aria-labelledby="footer-institucional">
<h2 id="footer-institucional">{{ __('portal.footer_institutional') }}</h2>
<ul class="ciata-footer__links">
<li><a href="https://ciata.org.br/o-ciata">O CIATA</a></li>
<li><a href="https://ciata.org.br/publicacoes">Publicações</a></li>
<li><a href="https://ciata.org.br/participe">Participe</a></li>
<li><a href="https://ciata.org.br/acessibilidade">Acessibilidade</a></li>
<li><a href="https://ciata.org.br/politica-de-privacidade">Política de Privacidade</a></li>
<li><a href="https://ciata.org.br/biblioteca/privacidade">Privacidade na Biblioteca Virtual CIATA</a></li>
<li><a href="https://ciata.org.br/biblioteca/termos-de-uso">Termos de Uso da Biblioteca Virtual CIATA</a></li>
<li><a href="https://ciata.org.br/transparencia">Transparência</a></li>
<li><a href="https://ciata.org.br/contato">Contato</a></li>
</ul>
</section>

<section class="ciata-footer__column" aria-labelledby="footer-redes">
<h2 id="footer-redes">{{ __('portal.footer_social') }}</h2>
<ul class="ciata-footer__links">
<li><a href="https://www.linkedin.com/company/ciatabr/" target="_blank" rel="noopener noreferrer">LinkedIn<span class="sr-only">, {{ __('portal.opens_new_window') }}</span></a></li>
<li><a href="https://www.facebook.com/ciata.br" target="_blank" rel="noopener noreferrer">Facebook<span class="sr-only">, {{ __('portal.opens_new_window') }}</span></a></li>
<li><a href="https://www.instagram.com/ciatabr" target="_blank" rel="noopener noreferrer">Instagram<span class="sr-only">, {{ __('portal.opens_new_window') }}</span></a></li>
<li><a href="https://x.com/ciatabr" target="_blank" rel="noopener noreferrer">X<span class="sr-only">, {{ __('portal.opens_new_window') }}</span></a></li>
<li><a href="https://www.youtube.com/@ciatabr" target="_blank" rel="noopener noreferrer">YouTube<span class="sr-only">, {{ __('portal.opens_new_window') }}</span></a></li>
</ul>
</section>
</div>
<div class="ciata-footer__bottom">© {{ date('Y') }} Centro de Inclusão Através da Tecnologia Assistiva</div>
</footer>

<script>
document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;

    button.addEventListener('click', () => {
        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';
        button.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        button.textContent = reveal
            ? @json(__('portal.hide_password'))
            : @json(__('portal.show_password'));
    });
});
</script>
@if(session('status'))
<script>
document.getElementById('flash-status')?.focus();
</script>
@endif
</body>
</html>
