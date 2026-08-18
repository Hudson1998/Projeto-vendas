@extends('layouts.app')

@section('title', 'Favoritos · HR Moda Online')

@section('content')
<section class="wrap page-section">
  <div class="page-header">
    <h1 class="page-header__title">Favoritos</h1>
  </div>

  @if ($produtos->isEmpty())
    <div class="empty-state is-visible">
      <span class="empty-state__title">Você ainda não tem favoritos</span>
      <span class="empty-state__subtitle">Toque no coração de uma peça para guardá-la aqui.</span>
      <a href="{{ route('home') }}#colecao" class="btn btn-outline" style="margin-top: 20px;">Ver coleção</a>
    </div>
  @else
    <div class="product-grid" id="favorites-grid">
      @foreach ($produtos as $produto)
        <div class="product-card" data-product-id="{{ $produto->id }}">
          <div class="product-card__image-wrap">
            <img class="product-card__image" src="{{ asset($produto->imagem) }}" alt="{{ $produto->nome }}">
            <button type="button" class="btn-favorite is-active" data-id="{{ $produto->id }}" title="Remover dos favoritos">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.6"><path d="M12 21s-7.5-4.6-10-9.1C.4 8.3 2 4.5 5.6 4c2-.3 3.9.6 6.4 3 2.5-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.3 3.6 7.9-2.5 4.5-10 9.1-10 9.1z"/></svg>
            </button>
          </div>
          <div class="product-card__body">
            <span class="product-card__category">{{ $produto->categoria }}</span>
            <span class="product-card__name">{{ $produto->nome }}</span>
            <span class="product-card__price">R$ {{ number_format($produto->preco, 2, ',', '.') }}</span>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</section>

<script>
  document.getElementById('favorites-grid')?.addEventListener('click', (e) => {
    const botao = e.target.closest('.btn-favorite');
    if (!botao) return;

    const productId = botao.dataset.id;
    botao.disabled = true;

    fetch(`${window.FAVORITES_TOGGLE_URL}/${productId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        Accept: 'application/json',
      },
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.favoritado) {
          botao.closest('.product-card').remove();
        }
      })
      .finally(() => {
        botao.disabled = false;
      });
  });
</script>
@endsection
