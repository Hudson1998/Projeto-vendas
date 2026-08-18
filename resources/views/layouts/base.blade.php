<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'HR Moda Online')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&family=Italiana&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>

@include('partials.page-loader')

<div id="ajax-content">
<header class="simple-header">
  <div class="wrap simple-header__inner">
    <a href="{{ route('home') }}" class="brand">
      <span class="brand__name">HR</span>
      <span class="brand__tagline">Moda Online</span>
    </a>
    <a href="{{ route('home') }}" class="account-link">&larr; Voltar à loja</a>
  </div>
</header>

<main class="simple-main">
  <div class="wrap">
    @yield('content')
  </div>
</main>
</div>

<script src="{{ asset('js/ajax-nav.js') }}"></script>
<script src="{{ asset('js/flash.js') }}"></script>
<script src="{{ asset('js/cep.js') }}"></script>
@stack('scripts')
</body>
</html>
