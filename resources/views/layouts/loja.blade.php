<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Painel da Loja · HR Moda Online')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&family=Italiana&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset_v('css/styles.css') }}">
<link rel="stylesheet" href="{{ asset_v('css/admin.css') }}">
</head>
<body class="admin-body loja-body">

@include('partials.page-loader')

<div id="ajax-content">
<div class="admin-shell">
  <div class="admin-sidebar-overlay" id="admin-sidebar-overlay"></div>

  <aside class="admin-sidebar" id="admin-sidebar">
    <a href="{{ route('loja.dashboard') }}" class="admin-brand">
      <span class="brand__name">HR</span>
      <span class="admin-brand__label">Painel da Loja</span>
    </a>

    {{-- identificacao do papel: e a primeira coisa lida ao entrar no painel --}}
    <div class="loja-selo">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 9h16l-1.4-4.2A1 1 0 0 0 17.6 4H6.4a1 1 0 0 0-1 .8L4 9z"></path><path d="M5 9v10h14V9"></path></svg>
      Lojista
    </div>

    <div class="loja-identidade">
      <span class="loja-identidade__nome">{{ auth()->user()->loja->nomeExibicao() }}</span>
      <span class="loja-identidade__local">{{ collect([auth()->user()->loja->envio_cidade, auth()->user()->loja->envio_estado])->filter()->implode(" · ") ?: "Endereço de envio não cadastrado" }}</span>
    </div>

    <nav class="admin-sidenav">
      <a href="{{ route('loja.dashboard') }}" class="admin-sidenav__link @if(request()->routeIs('loja.dashboard')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="9" rx="1"></rect><rect x="14" y="3" width="7" height="5" rx="1"></rect><rect x="14" y="12" width="7" height="9" rx="1"></rect><rect x="3" y="16" width="7" height="5" rx="1"></rect></svg>
        Dashboard
      </a>
      <a href="{{ route('loja.esteira') }}" class="admin-sidenav__link @if(request()->routeIs('loja.esteira')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="5" height="16" rx="1"></rect><rect x="10" y="4" width="5" height="11" rx="1"></rect><rect x="17" y="4" width="4" height="7" rx="1"></rect></svg>
        Esteira de pedidos
      </a>
      <a href="{{ route('loja.carteira') }}" class="admin-sidenav__link @if(request()->routeIs('loja.carteira')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v2"></path><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2z"></path></svg>
        Carteira
      </a>
      <a href="{{ route('loja.documentos') }}" class="admin-sidenav__link @if(request()->routeIs('loja.documentos')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path><path d="M14 2v6h6"></path></svg>
        Documentos
      </a>
      <a href="{{ route('loja.pedidos') }}" class="admin-sidenav__link @if(request()->routeIs('loja.pedidos')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path><path d="M14 2v6h6M9 13h6M9 17h6"></path></svg>
        Pedidos
      </a>
      <a href="{{ route('loja.transportadoras') }}" class="admin-sidenav__link @if(request()->routeIs('loja.transportadoras*')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="1" y="6" width="15" height="12" rx="1"></rect><path d="M16 10h4l3 3v5h-7"></path><circle cx="6" cy="19" r="2"></circle><circle cx="18" cy="19" r="2"></circle></svg>
        Transportadoras
      </a>
      <a href="{{ route('loja.clientes') }}" class="admin-sidenav__link @if(request()->routeIs('loja.clientes')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        Visitantes
      </a>
      <a href="{{ route('loja.produtos') }}" class="admin-sidenav__link @if(request()->routeIs('loja.produtos')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="M3.27 6.96 12 12l8.73-5.04M12 22V12"></path></svg>
        Produtos
      </a>
      <a href="{{ route('loja.perfil') }}" class="admin-sidenav__link @if(request()->routeIs('loja.perfil*')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 21v-2a5 5 0 0 1 5-5h8a5 5 0 0 1 5 5v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        Perfil da loja
      </a>
    </nav>

    <div class="admin-sidenav__footer">
      <a href="{{ route('home') }}" class="admin-sidenav__link">Ver loja</a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="admin-sidenav__link admin-sidenav__link--exit">Sair</button>
      </form>
    </div>
  </aside>

  <div class="admin-content">
    <header class="admin-topbar">
      <button type="button" id="admin-sidebar-toggle" class="admin-sidebar-toggle" aria-label="Abrir menu">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
      <span class="admin-topbar__title">@yield('title', 'Painel da Loja')</span>
    </header>

    {{-- Faixa do papel: diz de quem e a area e leva a carteira.

         Fica FORA da .admin-topbar de proposito -- aquela e display:none acima
         de 900px, entao a carteira que morava la sumia no desktop e so aparecia
         no celular. Esta faixa aparece nos dois. --}}
    @php($carteiraTopo = \App\Support\CarteiraDaLoja::resumo(auth()->user()->loja))
    <div class="loja-faixa">
      <span class="loja-faixa__papel">
        <span class="loja-faixa__ponto"></span>
        Área do lojista
      </span>
      <span class="loja-faixa__contexto">— você está gerenciando {{ auth()->user()->loja->nomeExibicao() }}</span>

      <a href="{{ route('loja.carteira') }}" class="carteira-chip" title="Abrir a carteira">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v2"></path><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H5a2 2 0 0 1-2-2z"></path><circle cx="17" cy="14" r="1.3" fill="currentColor" stroke="none"></circle></svg>
        <span class="carteira-chip__valores">
          <span class="carteira-chip__disponivel">R$ {{ number_format($carteiraTopo['disponivel'], 2, ',', '.') }}</span>
          <span class="carteira-chip__receber">a receber R$ {{ number_format($carteiraTopo['a_receber'], 2, ',', '.') }}</span>
        </span>
      </a>
    </div>

    <main class="admin-main">
      @yield('content')
    </main>
  </div>
</div>
</div>

<script src="{{ asset_v('js/ajax-nav.js') }}"></script>
<script src="{{ asset_v('js/admin-nav.js') }}"></script>
<script src="{{ asset_v('js/flash.js') }}"></script>
<script src="{{ asset_v('js/loja-dashboard.js') }}"></script>
{{-- fica fora do #ajax-content de proposito, para carregar depois dos JS
     globais, mas precisa de id proprio: o ajax-nav troca este bloco a cada
     navegacao, senao o script da pagina nunca roda sem um reload completo. --}}
<div id="ajax-scripts">@stack('scripts')</div>
</body>
</html>
