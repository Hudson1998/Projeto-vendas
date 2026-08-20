<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Painel · HR Moda Online')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&family=Italiana&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset_v('css/styles.css') }}">
<link rel="stylesheet" href="{{ asset_v('css/admin.css') }}">
</head>
<body class="admin-body">

@include('partials.page-loader')

<div id="ajax-content">
<div class="admin-shell">
  <div class="admin-sidebar-overlay" id="admin-sidebar-overlay"></div>

  <aside class="admin-sidebar" id="admin-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
      <span class="brand__name">HR</span>
      <span class="admin-brand__label">Painel Admin</span>
    </a>

    <nav class="admin-sidenav">
      <a href="{{ route('admin.dashboard') }}" class="admin-sidenav__link @if(request()->routeIs('admin.dashboard')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="9" rx="1"></rect><rect x="14" y="3" width="7" height="5" rx="1"></rect><rect x="14" y="12" width="7" height="9" rx="1"></rect><rect x="3" y="16" width="7" height="5" rx="1"></rect></svg>
        Dados
      </a>
      <a href="{{ route('admin.charts') }}" class="admin-sidenav__link @if(request()->routeIs('admin.charts')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 20V10M12 20V4M20 20v-7"></path></svg>
        Gráficos
      </a>
      <a href="{{ route('admin.clientes') }}" class="admin-sidenav__link @if(request()->routeIs('admin.clientes')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        Clientes cadastrados
      </a>
      <a href="{{ route('admin.produtos') }}" class="admin-sidenav__link @if(request()->routeIs('admin.produtos')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="M3.27 6.96 12 12l8.73-5.04M12 22V12"></path></svg>
        Produtos cadastrados
      </a>
      <a href="{{ route('admin.emails') }}" class="admin-sidenav__link @if(request()->routeIs('admin.emails')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 6-10 7L2 6"></path></svg>
        Emails
      </a>
      <a href="{{ route('admin.faturamento') }}" class="admin-sidenav__link @if(request()->routeIs('admin.faturamento')) is-active @endif">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z"></path><path d="M14 2v6h6M9 13h6M9 17h6"></path></svg>
        Faturamento
      </a>
    </nav>

    <div class="admin-sidenav__footer">
      <a href="{{ route('admin.products.create') }}" class="admin-sidenav__link @if(request()->routeIs('admin.products.create')) is-active @endif">+ Nova peça</a>
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
      <span class="admin-topbar__title">@yield('title', 'Painel')</span>
    </header>

    <main class="admin-main">
      @yield('content')
    </main>
  </div>
</div>
</div>

<script src="{{ asset_v('js/ajax-nav.js') }}"></script>
<script src="{{ asset_v('js/admin-nav.js') }}"></script>
<script src="{{ asset_v('js/flash.js') }}"></script>
</body>
</html>
