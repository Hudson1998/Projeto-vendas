<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'HR Moda Feminina')</title>
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
@php
  $categoriasDropdown = collect(['Camisas', 'Calças', 'Acessórios', 'Chapéus', 'Perfumes', 'Calçados', 'Vestidos', 'Saias'])
      ->merge(\App\Models\Product::query()->distinct()->pluck('categoria'))
      ->unique()
      ->values();

  $quantidadeCarrinho = auth()->check()
      ? \App\Models\CartItem::where('user_id', auth()->id())->sum('quantidade')
      : 0;
@endphp
<header class="site-header">
  <div class="wrap site-header__inner">
    <a href="{{ route('home') }}" class="brand">
      <span class="brand__name">HR</span>
      <span class="brand__tagline">Moda Online</span>
    </a>

    <div class="header-search">
      <div class="category-dropdown" id="category-dropdown">
        <button type="button" id="category-toggle" class="category-toggle" aria-haspopup="listbox" aria-expanded="false">
          <span id="category-label">Todos</span>
          <svg class="category-chevron" width="10" height="6" viewBox="0 0 10 6"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg>
        </button>
        <ul class="category-menu" id="category-menu" role="listbox">
          <li role="presentation"><button type="button" class="category-option is-active" data-cat="Todos" role="option">Todos</button></li>
          @foreach ($categoriasDropdown as $categoria)
            <li role="presentation"><button type="button" class="category-option" data-cat="{{ $categoria }}" role="option">{{ $categoria }}</button></li>
          @endforeach
        </ul>
      </div>
      <div class="search-box">
        <button type="button" id="search-icon" class="search-icon" title="Buscar">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#a8a8ae" stroke-width="2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.5" y2="16.5"></line></svg>
        </button>
        <input type="text" id="search-input" class="search-input" placeholder="Buscar peça...">
      </div>
    </div>

    <div class="header-account">
      @auth
        @if (auth()->user()->isAdmin())
          <a href="{{ route('admin.dashboard') }}" class="account-link">Painel Admin</a>
        @endif

        <a href="{{ route('cart.index') }}" class="cart-icon" title="Carrinho">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="9" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
          <span class="cart-icon__badge" id="cart-badge" @if($quantidadeCarrinho < 1) style="display:none" @endif>{{ $quantidadeCarrinho }}</span>
        </a>

        <div class="profile-dropdown" id="profile-dropdown">
          <button type="button" id="profile-toggle" class="profile-avatar" aria-haspopup="menu" aria-expanded="false">
            {{ auth()->user()->initials() }}
          </button>
          <div class="profile-menu" id="profile-menu" role="menu">
            <div class="profile-menu__header">
              <span class="profile-menu__name">{{ auth()->user()->name }}</span>
              <span class="profile-menu__email">{{ auth()->user()->email }}</span>
            </div>
            <a href="{{ route('profile.edit') }}" class="profile-menu__item" role="menuitem">Configurações</a>
            <a href="{{ route('orders.index') }}" class="profile-menu__item" role="menuitem">Acompanhar pedido</a>
            <a href="{{ route('orders.index') }}#realizadas" class="profile-menu__item" role="menuitem">Compras realizadas</a>
            <a href="{{ route('orders.index') }}#canceladas" class="profile-menu__item" role="menuitem">Compras canceladas</a>
            <a href="{{ route('favorites.index') }}" class="profile-menu__item" role="menuitem">Favoritos</a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="profile-menu__item profile-menu__item--exit" role="menuitem">Sair</button>
            </form>
          </div>
        </div>
      @else
        <a href="{{ route('login') }}" class="account-link">Entrar</a>
        <a href="{{ route('register') }}" class="account-link account-link--primary">Cadastrar</a>
      @endauth
    </div>
  </div>
</header>

@if (session('status'))
  <div class="wrap">
    <div class="flash-status">{{ session('status') }}</div>
  </div>
@endif

@yield('content')

<footer class="site-footer">
  <div class="wrap footer__inner">
    <div class="footer__brand">
      <span class="footer__brand-name">HR</span>
      <span class="footer__brand-tagline">Moda Online</span>
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

<div class="modal-overlay" id="confirm-modal" aria-hidden="true">
  <div class="modal-box" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title">
    <h3 class="modal-box__title" id="confirm-modal-title">Confirmar</h3>
    <p class="modal-box__text" id="confirm-modal-text">Tem certeza?</p>
    <div class="modal-box__actions">
      <button type="button" class="btn btn-outline" id="confirm-modal-cancel">Cancelar</button>
      <button type="button" class="btn btn-primary" id="confirm-modal-confirm">Confirmar</button>
    </div>
  </div>
</div>

@isset($produtos)
  @php
    $favoritosIds = auth()->check()
        ? \App\Models\Favorite::where('user_id', auth()->id())->pluck('product_id')
        : collect();

    $produtosParaJs = $produtos->map(fn ($p) => [
        'id' => $p->id,
        'nome' => $p->nome,
        'preco' => 'R$ '.number_format($p->preco, 2, ',', '.'),
        'categoria' => $p->categoria,
        'url' => asset($p->imagem),
        'favoritado' => $favoritosIds->contains($p->id),
    ]);
  @endphp
  <script>
    window.PRODUTOS = @json($produtosParaJs);
  </script>
@endisset
<script>
  window.IS_AUTHENTICATED = @json(auth()->check());
  window.LOGIN_URL = "{{ route('login') }}";
  window.CART_STORE_URL = "{{ route('cart.store') }}";
  window.BUSCAS_URL = "{{ route('buscas.store') }}";
  window.FAVORITES_TOGGLE_URL = "{{ url('/favoritos') }}";
</script>
</div>

<script src="{{ asset('js/ajax-nav.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/flash.js') }}"></script>
</body>
</html>
