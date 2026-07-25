@extends('layouts.app')

@section('title', $product->nome.' · HR Moda Feminina')

@section('content')
<section class="wrap page-section">
  <a href="{{ route('home') }}#colecao" class="link-btn">&larr; Voltar à coleção</a>

  @if ($errors->any())
    <div class="form-status form-status--error">
      @foreach ($errors->all() as $error)
        <p>{{ $error }}</p>
      @endforeach
    </div>
  @endif

  <div class="product-detail">
    <img class="product-detail__image" src="{{ asset($product->imagem) }}" alt="{{ $product->nome }}">

    <div class="product-detail__info">
      <span class="product-card__category">{{ $product->categoria }}</span>
      <h1 class="product-detail__name">{{ $product->nome }}</h1>
      <span class="product-detail__price">R$ {{ number_format($product->preco, 2, ',', '.') }}</span>

      @if ($mediaAvaliacao)
        <div class="product-detail__rating">
          <div class="star-rating star-rating--readonly">
            @for ($i = 1; $i <= 5; $i++)
              <span class="star {{ $i <= round($mediaAvaliacao) ? 'is-filled' : '' }}">&#9733;</span>
            @endfor
          </div>
          <span class="product-detail__rating-count">{{ $mediaAvaliacao }} ({{ $reviews->count() }} avaliaç{{ $reviews->count() === 1 ? 'ão' : 'ões' }})</span>
        </div>
      @endif

      <p class="product-detail__stock {{ $product->estoque > 0 ? '' : 'product-detail__stock--esgotado' }}">
        @if ($product->estoque > 0)
          Em estoque: {{ $product->estoque }} {{ $product->estoque === 1 ? 'unidade' : 'unidades' }}
        @else
          Esgotado
        @endif
      </p>

      @if ($product->descricao)
        <p class="product-detail__description">{{ $product->descricao }}</p>
      @endif

      @if ($product->estoque > 0)
        <form id="add-to-cart-form" data-no-ajax>
          @if (!empty($product->tamanhos))
            <div class="field">
              <label>Tamanho</label>
              <div class="radio-group radio-group--inline">
                @foreach ($product->tamanhos as $tamanho)
                  <label class="radio">
                    <input type="radio" name="tamanho" value="{{ $tamanho }}" {{ $loop->first ? 'checked' : '' }}>
                    {{ $tamanho }}
                  </label>
                @endforeach
              </div>
            </div>
          @endif

          @if (!empty($product->cores))
            <div class="field">
              <label>Cor</label>
              <div class="radio-group radio-group--inline">
                @foreach ($product->cores as $cor)
                  <label class="radio">
                    <input type="radio" name="cor" value="{{ $cor['nome'] }}" {{ $loop->first ? 'checked' : '' }}>
                    <span class="color-swatch" style="background: {{ $cor['hex'] }};"></span>
                    {{ $cor['nome'] }}
                  </label>
                @endforeach
              </div>
            </div>
          @endif

          <div class="field">
            <label for="quantidade">Quantidade</label>
            <input type="number" id="quantidade" name="quantidade" min="1" max="{{ min(20, $product->estoque) }}" value="1">
          </div>

          <button type="submit" class="btn btn-primary btn-block" id="add-to-cart-btn">Adicionar ao carrinho</button>
        </form>
      @endif

      <div class="product-detail__store">
        <span class="product-detail__store-label">Vendido por</span>
        <strong>HR Moda Feminina</strong>
        <p>Loja própria de moda feminina online, com curadoria e envio para todo o Brasil.</p>
      </div>
    </div>
  </div>

  <div class="reviews-section">
    <h2 class="reviews-section__title">Avaliações</h2>

    @if ($jaComprou)
      <form method="POST" action="{{ route('products.reviews.store', $product) }}" class="review-form">
        @csrf
        <span class="rating-form__label">{{ $minhaAvaliacao ? 'Sua avaliação' : 'Avalie esta peça' }}</span>
        <div class="star-rating">
          @for ($i = 5; $i >= 1; $i--)
            <input type="radio" id="avaliacao-{{ $i }}" name="avaliacao" value="{{ $i }}" {{ optional($minhaAvaliacao)->avaliacao == $i ? 'checked' : '' }}>
            <label for="avaliacao-{{ $i }}" title="{{ $i }} estrelas">&#9733;</label>
          @endfor
        </div>
        <div class="field">
          <textarea name="comentario" placeholder="Conte o que achou da peça (opcional)">{{ old('comentario', optional($minhaAvaliacao)->comentario) }}</textarea>
        </div>
        <button type="submit" class="btn btn-outline">{{ $minhaAvaliacao ? 'Atualizar avaliação' : 'Enviar avaliação' }}</button>
      </form>
    @endif

    @forelse ($reviews as $review)
      <div class="review-card">
        <div class="review-card__header">
          <span class="review-card__author">{{ $review->user->name }}</span>
          <div class="star-rating star-rating--readonly">
            @for ($i = 1; $i <= 5; $i++)
              <span class="star {{ $i <= $review->avaliacao ? 'is-filled' : '' }}">&#9733;</span>
            @endfor
          </div>
          <span class="review-card__date">{{ $review->created_at->format('d/m/Y') }}</span>
        </div>
        @if ($review->comentario)
          <p class="review-card__body">{{ $review->comentario }}</p>
        @endif
      </div>
    @empty
      <p class="empty-state__subtitle">Nenhuma avaliação ainda.</p>
    @endforelse
  </div>
</section>

<script>
(function () {
  var form = document.getElementById('add-to-cart-form');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!window.IS_AUTHENTICATED) {
      window.ajaxNavigate ? window.ajaxNavigate(window.LOGIN_URL) : (window.location.href = window.LOGIN_URL);
      return;
    }

    var botao = document.getElementById('add-to-cart-btn');
    var tamanho = form.querySelector('input[name="tamanho"]:checked');
    var cor = form.querySelector('input[name="cor"]:checked');
    var quantidade = form.querySelector('input[name="quantidade"]').value;

    botao.disabled = true;

    fetch(window.CART_STORE_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        Accept: 'application/json',
      },
      body: JSON.stringify({
        product_id: {{ $product->id }},
        quantidade: quantidade,
        tamanho: tamanho ? tamanho.value : null,
        cor: cor ? cor.value : null,
      }),
    })
      .then((res) => res.json().then((data) => ({ ok: res.ok, data })))
      .then(({ ok, data }) => {
        mostrarToast(ok ? data.message : (data.message || 'Não foi possível adicionar ao carrinho.'), ok ? 'sucesso' : 'erro');
        if (ok) atualizarBadgeCarrinho(data.quantidadeCarrinho);
      })
      .catch(() => mostrarToast('Não foi possível adicionar ao carrinho.', 'erro'))
      .finally(() => {
        botao.disabled = false;
      });
  });
})();
</script>
@endsection
