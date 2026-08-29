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
<link rel="stylesheet" href="{{ asset_v('css/styles.css') }}">
<link rel="stylesheet" href="{{ asset_v('css/admin.css') }}">
</head>
<body>

@include('partials.page-loader')

<div id="ajax-content">
@php
  $quantidadeCarrinho = auth()->check()
      ? \App\Models\CartItem::where('user_id', auth()->id())->sum('quantidade')
      : 0;

  $notificacoes = auth()->check()
      ? \App\Models\Order::where('user_id', auth()->id())
          ->whereIn('status', ['concluido', 'cancelado'])
          ->latest('updated_at')
          ->take(5)
          ->get()
      : collect();
@endphp
<header class="site-header">
  <div class="wrap site-header__inner">
    <a href="{{ route('home') }}" class="brand">
      <span class="brand__name">HR</span>
      <span class="brand__tagline">Moda Online</span>
    </a>

    <div class="header-search">
      {{-- so aparece onde existe vitrine para filtrar: fora da home o painel
           nao teria dados e viraria um controle morto no header --}}
      @isset($produtos)
        <div class="filter-dropdown" id="filter-dropdown">
          <button type="button" id="filter-toggle" class="filter-toggle" aria-haspopup="dialog" aria-expanded="false" aria-controls="filter-panel" title="Filtrar">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="7" y1="12" x2="17" y2="12"></line><line x1="10" y1="18" x2="14" y2="18"></line></svg>
            <span class="filter-toggle__badge" id="filter-badge" hidden>0</span>
          </button>
          <div class="filter-panel" id="filter-panel" role="dialog" aria-label="Filtrar produtos">
            <div class="filter-panel__head">
              <span class="filter-panel__eyebrow">Filtrar</span>
              <button type="button" class="filter-panel__clear" id="filter-clear">Limpar tudo</button>
            </div>
            {{-- montado pelo app.js a partir de window.FILTRO_ARVORE --}}
            <div id="filter-body"></div>
            <div class="filter-panel__foot">
              <button type="button" class="filter-panel__apply" id="filter-apply">Aplicar</button>
            </div>
          </div>
        </div>
      @endisset
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
          <a href="{{ route('admin.dashboard') }}" class="account-link header-account__item--desktop">Painel Admin</a>
        @elseif (auth()->user()->loja)
          {{-- o caminho de volta ao painel: sem isto o lojista que clica em
               "Ver loja" so retorna digitando /loja na barra do navegador --}}
          <a href="{{ route('loja.dashboard') }}" class="account-link account-link--lojista header-account__item--desktop">Painel da Loja</a>
        @endif

        <a href="{{ route('cart.index') }}" class="cart-icon" title="Carrinho">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="9" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
          <span class="cart-icon__badge" id="cart-badge" @if($quantidadeCarrinho < 1) style="display:none" @endif>{{ $quantidadeCarrinho }}</span>
        </a>

        <button type="button" id="mobile-menu-toggle" class="mobile-menu-toggle" aria-haspopup="menu" aria-expanded="false" aria-controls="mobile-menu" title="Menu">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>

        <div class="notification-dropdown header-account__item--desktop" id="notification-dropdown">
          <button type="button" id="notification-toggle" class="notification-bell" aria-haspopup="menu" aria-expanded="false" title="Notificações">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            @if ($notificacoes->isNotEmpty())
              <span class="notification-bell__dot"></span>
            @endif
          </button>
          <div class="notification-menu" id="notification-menu" role="menu">
            <div class="notification-menu__header">Notificações</div>
            @forelse ($notificacoes as $pedido)
              <div class="notification-menu__item">
                <span class="notification-menu__text">Pedido #{{ $pedido->id }} foi {{ $pedido->status === 'concluido' ? 'concluído' : 'cancelado' }}</span>
                <span class="notification-menu__date">{{ $pedido->updated_at->format('d/m/Y') }}</span>
              </div>
            @empty
              <div class="notification-menu__empty">Nenhuma notificação no momento.</div>
            @endforelse
          </div>
        </div>

        <div class="profile-dropdown header-account__item--desktop" id="profile-dropdown">
          {{-- aria-label fixo: com a foto no lugar das iniciais o botao ficaria
               sem nome acessivel, porque a imagem e decorativa (alt vazio) --}}
          <button type="button" id="profile-toggle" class="profile-avatar" aria-haspopup="menu" aria-expanded="false" aria-label="Menu da conta">
            @if (auth()->user()->foto)
              <img src="{{ asset(auth()->user()->foto) }}" alt="">
            @else
              {{ auth()->user()->initials() }}
            @endif
          </button>
          <div class="profile-menu" id="profile-menu" role="menu">
            <div class="profile-menu__header">
              <span class="profile-menu__name">{{ auth()->user()->name }}</span>
              <span class="profile-menu__email">{{ auth()->user()->email }}</span>
            </div>
            <a href="{{ route('profile.edit') }}" class="profile-menu__item" role="menuitem">Configurações</a>
            <a href="{{ route('orders.tracking') }}" class="profile-menu__item" role="menuitem">Acompanhar pedido</a>
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
        <button type="button" id="mobile-menu-toggle" class="mobile-menu-toggle" aria-haspopup="menu" aria-expanded="false" aria-controls="mobile-menu" title="Menu">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
        </button>
        <a href="{{ route('login') }}" class="account-link header-account__item--desktop">Entrar</a>
        <a href="{{ route('register') }}" class="account-link account-link--primary header-account__item--desktop">Cadastrar</a>
      @endauth
    </div>
  </div>
</header>

<div class="mobile-menu" id="mobile-menu" aria-hidden="true">
  <div class="mobile-menu__panel">
    @auth
      <div class="mobile-menu__profile">
        <span class="mobile-menu__profile-name">{{ auth()->user()->name }}</span>
        <span class="mobile-menu__profile-email">{{ auth()->user()->email }}</span>
      </div>
      <a href="{{ route('home') }}" class="mobile-menu__item">Início</a>
      @if (auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="mobile-menu__item">Painel Admin</a>
      @elseif (auth()->user()->loja)
        <a href="{{ route('loja.dashboard') }}" class="mobile-menu__item">Painel da Loja</a>
      @endif
      <a href="{{ route('profile.edit') }}" class="mobile-menu__item">Configurações</a>
      <a href="{{ route('orders.tracking') }}" class="mobile-menu__item">Acompanhar pedido</a>
      <a href="{{ route('orders.index') }}#realizadas" class="mobile-menu__item">Compras realizadas</a>
      <a href="{{ route('orders.index') }}#canceladas" class="mobile-menu__item">Compras canceladas</a>
      <a href="{{ route('favorites.index') }}" class="mobile-menu__item">Favoritos</a>

      <div class="mobile-menu__notifications">
        <div class="mobile-menu__notifications-title">Notificações</div>
        @forelse ($notificacoes as $pedido)
          <div class="mobile-menu__notification">
            <span class="notification-menu__text">Pedido #{{ $pedido->id }} foi {{ $pedido->status === 'concluido' ? 'concluído' : 'cancelado' }}</span>
            <span class="notification-menu__date">{{ $pedido->updated_at->format('d/m/Y') }}</span>
          </div>
        @empty
          <div class="notification-menu__empty">Nenhuma notificação no momento.</div>
        @endforelse
      </div>

      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="mobile-menu__item mobile-menu__item--exit">Sair</button>
      </form>
    @else
      <a href="{{ route('home') }}" class="mobile-menu__item">Início</a>
      <a href="{{ route('login') }}" class="mobile-menu__item">Entrar</a>
      <a href="{{ route('register') }}" class="mobile-menu__item">Cadastrar</a>
    @endauth
  </div>
</div>

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
    <span class="footer__copy">© {{ date('Y') }} HR Moda Online</span>
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

    $mapParaJs = fn ($colecao) => $colecao->map(fn ($p) => [
        'id' => $p->id,
        'nome' => $p->nome,
        'preco' => 'R$ '.number_format($p->preco, 2, ',', '.'),
        'precoPromocional' => $p->preco_promocional ? 'R$ '.number_format($p->preco_promocional, 2, ',', '.') : null,
        'categoria' => $p->categoria,
        // chave usada pelo filtro da vitrine: nome da subclasse (Bolsa, Vestido...)
        // quando o produto ja foi classificado, senao cai na categoria livre
        'subclasse' => $p->subclass->nome ?? $p->categoria,
        'url' => asset($p->imagem),
        'favoritado' => $favoritosIds->contains($p->id),
        // usados pelos filtros da vitrine; os carrosseis nao carregam esses
        // agregados e caem no valor neutro
        'precoNumerico' => (float) ($p->preco_promocional ?? $p->preco),
        'vendas' => (int) ($p->quantidade_vendida ?? 0),
        'visualizacoes' => (int) ($p->visualizacoes ?? 0),
        'tamanhos' => $p->tamanhos_disponiveis ?? [],
    ]);

    $produtosParaJs = $mapParaJs($produtos);
  @endphp
  <script>
    window.PRODUTOS = @json($produtosParaJs);
    window.FILTRO_ARVORE = @json($arvoreFiltros ?? []);
    window.CARROSSEL_MAIS_COMPRADOS = @json($mapParaJs($carrosselMaisComprados ?? collect()));
    window.CARROSSEL_MAIS_VISITADOS = @json($mapParaJs($carrosselMaisVisitados ?? collect()));
    window.CARROSSEL_PROMOCOES = @json($mapParaJs($carrosselPromocoes ?? collect()));
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

<script src="{{ asset_v('js/ajax-nav.js') }}"></script>
<script src="{{ asset_v('js/app.js') }}"></script>
<script src="{{ asset_v('js/flash.js') }}"></script>
<script src="{{ asset_v('js/cep.js') }}"></script>
<script src="{{ asset_v('js/lojista-cadastro.js') }}"></script>
{{-- fica fora do #ajax-content de proposito, para carregar depois dos JS
     globais, mas precisa de id proprio: o ajax-nav troca este bloco a cada
     navegacao, senao o script da pagina nunca roda sem um reload completo. --}}
<div id="ajax-scripts">@stack('scripts')</div>
</body>
</html>
