<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'HR Moda Feminina')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;1,400&family=Italiana&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>

<header class="site-header">
  <div class="wrap site-header__inner">
    <div class="brand">
      <span class="brand__name">HR</span>
      <span class="brand__tagline">Moda Feminina</span>
    </div>
    <nav class="nav">
      <a href="#colecao" class="nav-link is-active" data-cat="Todos">Todos</a>
      @foreach ($categorias as $categoria)
        <a href="#colecao" class="nav-link" data-cat="{{ $categoria }}">{{ $categoria }}</a>
      @endforeach
      <div class="search-box">
        <button type="button" id="search-icon" class="search-icon" title="Buscar">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a8a8ae" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line></svg>
        </button>
        <input type="text" id="search-input" class="search-input" placeholder="Buscar peça...">
      </div>
    </nav>
  </div>
</header>

@yield('content')

<footer class="site-footer">
  <div class="wrap footer__inner">
    <div class="footer__brand">
      <span class="footer__brand-name">HR</span>
      <span class="footer__brand-tagline">Moda Feminina</span>
    </div>
    <div class="footer__social">
      <a href="#" title="Instagram">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#a8a8ae" stroke-width="1.6"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.2" cy="6.8" r="1" fill="#a8a8ae" stroke="none"></circle></svg>
      </a>
      <a href="#" title="WhatsApp">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#a8a8ae" stroke-width="1.6"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.7-1.2A9 9 0 1 0 12 3z"></path><path d="M8.8 9.2c0 3.3 2.7 6 6 6l1.4-1.4-2-1.3-1 .7a4.6 4.6 0 0 1-2.4-2.4l.7-1-1.3-2z" fill="#a8a8ae" stroke="none"></path></svg>
      </a>
      <a href="#" title="Facebook">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#a8a8ae" stroke-width="1.6"><path d="M14 8h3V4.5h-3c-2 0-3.5 1.6-3.5 3.5v2.5H8V14h2.5v6H14v-6h2.5l.5-3.5h-3V8.5c0-.3.2-.5.5-.5z" fill="#a8a8ae" stroke="none"></path></svg>
      </a>
      <a href="#" title="TikTok">
        <svg width="34" height="34" viewBox="0 0 24 24" fill="#a8a8ae"><path d="M16.5 3h-2.8v12.2a2.6 2.6 0 1 1-2.6-2.6c.2 0 .5 0 .7.1V9.8a5.5 5.5 0 1 0 4.7 5.4V9.3a6.6 6.6 0 0 0 3.7 1.1V7.6A3.9 3.9 0 0 1 16.5 3z"></path></svg>
      </a>
    </div>
    <span class="footer__copy">© {{ date('Y') }} HR Moda Feminina</span>
  </div>
</footer>

<script>
  window.PRODUTOS = @json($produtos->map(fn ($p) => [
      'nome' => $p->nome,
      'preco' => 'R$ '.number_format($p->preco, 2, ',', '.'),
      'categoria' => $p->categoria,
      'url' => asset($p->imagem),
  ]));
</script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
