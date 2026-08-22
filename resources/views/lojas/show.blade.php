@extends('layouts.app')

@section('title', $loja->nomeExibicao().' · HR Moda Online')

@section('content')
<section class="wrap page-section">
  <a href="{{ route('home') }}#colecao" class="link-btn">&larr; Voltar à coleção</a>

  <div class="store-header">
    @if ($loja->logotipo)
      <img class="store-header__logo" src="{{ asset($loja->logotipo) }}" alt="Logotipo de {{ $loja->nomeExibicao() }}">
    @else
      <span class="store-header__logo store-header__logo--initials" aria-hidden="true">{{ $loja->iniciais() }}</span>
    @endif

    <div class="store-header__info">
      <h1 class="store-header__name">{{ $loja->nomeExibicao() }}</h1>
      <span class="store-header__count">
        {{ $produtos->count() }} {{ $produtos->count() === 1 ? 'peça' : 'peças' }}
        · {{ $loja->fiscal_cidade }}/{{ $loja->fiscal_estado }}
      </span>
      @if ($loja->bio_loja)
        <p class="store-header__bio">{{ $loja->bio_loja }}</p>
      @endif
    </div>
  </div>

  @if ($produtos->isEmpty())
    <div class="empty-state is-visible">
      <span class="empty-state__title">Esta loja ainda não tem peças à venda</span>
      <span class="empty-state__subtitle">Volte em breve para ver as novidades dela.</span>
      <a href="{{ route('home') }}#colecao" class="btn btn-outline" style="margin-top: 20px;">Ver coleção</a>
    </div>
  @else
    <div class="product-grid">
      @foreach ($produtos as $produto)
        <div class="product-card">
          <a href="{{ route('products.show', $produto) }}" class="product-card__link">
            <div class="product-card__image-wrap">
              {{-- a loja mostra o catalogo inteiro dela numa pagina so, entao o
                   lazy evita puxar dezenas de imagens que ficam fora da dobra --}}
              <img class="product-card__image" src="{{ asset($produto->imagem) }}" alt="{{ $produto->nome }}" loading="lazy">
            </div>
            <div class="product-card__body">
              <span class="product-card__category">{{ $produto->categoria }}</span>
              <span class="product-card__name">{{ $produto->nome }}</span>
              @if ($produto->emPromocao())
                <span class="product-card__price product-card__price--old">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
                <span class="product-card__price product-card__price--promo">R$ {{ number_format($produto->preco_promocional, 2, ',', '.') }}</span>
              @else
                <span class="product-card__price">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
              @endif
            </div>
          </a>
        </div>
      @endforeach
    </div>
  @endif
</section>
@endsection
