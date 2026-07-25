@extends('layouts.app')

@section('title', 'Meus pedidos · HR Moda Feminina')

@section('content')
<section class="wrap page-section">
  <div class="page-header">
    <h1 class="page-header__title">Meus pedidos</h1>
  </div>

  @if (session('status'))
    <div class="form-status">{{ session('status') }}</div>
  @endif

  <div id="realizadas" class="orders-block">
    <h2 class="orders-block__title">Compras realizadas</h2>

    @forelse ($pedidosRealizados as $pedido)
      <div class="order-card">
        <div class="order-card__header">
          <div>
            <span class="order-card__id">Pedido #{{ $pedido->id }}</span>
            <span class="order-card__date">{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
          </div>
          <span class="order-status order-status--concluido">Concluído</span>
        </div>

        <div class="order-card__items">
          @foreach ($pedido->items as $item)
            <div class="order-card__item">
              <img src="{{ asset($item->product->imagem) }}" alt="{{ $item->product->nome }}">
              <span>{{ $item->product->nome }}{{ $item->tamanho || $item->cor ? ' ('.collect([$item->tamanho, $item->cor])->filter()->implode(' · ').')' : '' }} &times; {{ $item->quantidade }}</span>
              <span>R$ {{ number_format($item->preco_unitario * $item->quantidade, 2, ',', '.') }}</span>
            </div>
          @endforeach
        </div>

        <div class="order-card__footer">
          <span>{{ $pedido->tipo_entrega === 'entrega' ? 'Entrega: '.$pedido->endereco_entrega : 'Retirada na loja' }}</span>
          <span class="order-card__total">Total: R$ {{ number_format($pedido->total, 2, ',', '.') }}</span>
        </div>

        <div class="order-card__rating">
          @if (is_null($pedido->avaliacao))
            <form method="POST" action="{{ route('orders.rate', $pedido) }}" class="rating-form" data-auto-submit>
              @csrf
              <span class="rating-form__label">Avalie este pedido:</span>
              <div class="star-rating">
                @for ($i = 5; $i >= 1; $i--)
                  <input type="radio" id="estrela-{{ $pedido->id }}-{{ $i }}" name="avaliacao" value="{{ $i }}">
                  <label for="estrela-{{ $pedido->id }}-{{ $i }}" title="{{ $i }} estrelas">&#9733;</label>
                @endfor
              </div>
            </form>
          @else
            <span class="rating-form__label">Sua avaliação:</span>
            <div class="star-rating star-rating--readonly">
              @for ($i = 1; $i <= 5; $i++)
                <span class="star {{ $i <= $pedido->avaliacao ? 'is-filled' : '' }}">&#9733;</span>
              @endfor
            </div>
          @endif
        </div>

        <form method="POST" action="{{ route('orders.cancel', $pedido) }}" onsubmit="return confirm('Cancelar este pedido?');">
          @csrf
          <button type="submit" class="link-btn link-btn--danger">Cancelar pedido</button>
        </form>
      </div>
    @empty
      <p class="orders-block__empty">Nenhuma compra realizada ainda.</p>
    @endforelse
  </div>

  <div id="canceladas" class="orders-block">
    <h2 class="orders-block__title">Compras canceladas</h2>

    @forelse ($pedidosCancelados as $pedido)
      <div class="order-card order-card--cancelado">
        <div class="order-card__header">
          <div>
            <span class="order-card__id">Pedido #{{ $pedido->id }}</span>
            <span class="order-card__date">{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
          </div>
          <span class="order-status order-status--cancelado">Cancelado</span>
        </div>

        <div class="order-card__items">
          @foreach ($pedido->items as $item)
            <div class="order-card__item">
              <img src="{{ asset($item->product->imagem) }}" alt="{{ $item->product->nome }}">
              <span>{{ $item->product->nome }}{{ $item->tamanho || $item->cor ? ' ('.collect([$item->tamanho, $item->cor])->filter()->implode(' · ').')' : '' }} &times; {{ $item->quantidade }}</span>
              <span>R$ {{ number_format($item->preco_unitario * $item->quantidade, 2, ',', '.') }}</span>
            </div>
          @endforeach
        </div>

        <div class="order-card__footer">
          <span class="order-card__total">Total: R$ {{ number_format($pedido->total, 2, ',', '.') }}</span>
        </div>
      </div>
    @empty
      <p class="orders-block__empty">Nenhuma compra cancelada.</p>
    @endforelse
  </div>
</section>

<script>
  document.querySelectorAll('[data-auto-submit]').forEach((form) => {
    form.querySelectorAll('input[type="radio"]').forEach((radio) => {
      radio.addEventListener('change', () => form.submit());
    });
  });
</script>
@endsection
