@extends('layouts.app')

@section('title', 'Acompanhar pedido · HR Moda Online')

@section('content')
<section class="wrap page-section">
  <div class="page-header">
    <span class="page-header__eyebrow">Minha conta</span>
    <h1 class="page-header__title">Acompanhar pedido</h1>
  </div>

  @if (session('status'))
    <div class="form-status">{{ session('status') }}</div>
  @endif

  @forelse ($pedidos as $pedido)
    @php
      $etapaIndice = $pedido->etapaAcompanhamento();
      $etapaAtual = $pedido->etapaAtual();
      $capa = $pedido->items->first()?->product;
    @endphp

    <article class="tracking-card">
      <div class="tracking-card__top">
        @if ($capa)
          <img class="tracking-card__cover" src="{{ asset($capa->imagem) }}" alt="{{ $capa->nome }}">
        @endif

        <div class="tracking-card__summary">
          <div class="tracking-card__headline">
            <div class="tracking-card__identity">
              <span class="tracking-card__id">Pedido #{{ $pedido->id }}</span>
              <span class="tracking-card__date">
                Realizado em {{ $pedido->created_at->format('d/m/Y \à\s H:i') }} ·
                {{ $pedido->items->count() }} {{ $pedido->items->count() === 1 ? 'item' : 'itens' }}
              </span>
            </div>
            <span class="tracking-chip {{ $etapaIndice === 0 ? 'tracking-chip--espera' : '' }}">{{ $etapaAtual['chip'] }}</span>
          </div>

          <div class="tracking-card__facts">
            @if ($pedido->tipo_entrega === 'entrega')
              <div class="tracking-fact">
                <span class="tracking-fact__label">Entrega</span>
                <span class="tracking-fact__value">{{ $pedido->entrega_propria ? 'Entrega da loja' : ($pedido->transportadora?->nome ?? 'A definir') }}</span>
              </div>
            @else
              <div class="tracking-fact">
                <span class="tracking-fact__label">Modalidade</span>
                <span class="tracking-fact__value">Retirada na loja</span>
              </div>
            @endif

            <div class="tracking-fact">
              <span class="tracking-fact__label">Pagamento</span>
              <span class="tracking-fact__value">{{ ucfirst($pedido->forma_pagamento ?? '—') }}</span>
            </div>

            @if ($pedido->codigo_pagamento)
              <div class="tracking-fact">
                <span class="tracking-fact__label">Código</span>
                <span class="tracking-fact__value">{{ $pedido->codigo_pagamento }}</span>
              </div>
            @endif
          </div>

          <p class="tracking-card__message">{{ $etapaAtual['texto'] }}</p>

          {{-- pedido parado na primeira etapa e pedido nao pago: o caminho de
               volta para a tela da forma de pagamento tem de estar aqui, senao
               quem escolhe "Pagar depois" nao acha mais a cobranca --}}
          @if ($pedido->status_pagamento === 'pendente')
            <a href="{{ route('orders.pagamento', $pedido) }}" class="btn btn-primary tracking-card__pagar">
              Pagar agora
            </a>
          @endif
        </div>
      </div>

      <div class="tracking-card__steps">
        <div class="tracking-steps__header">
          <span class="tracking-steps__label">Situação do pedido</span>
          <span class="tracking-steps__counter">Etapa {{ $etapaIndice + 1 }} de {{ count($etapas) }}</span>
        </div>

        <div class="tracking-rail">
          <div class="tracking-rail__fill" style="width: {{ $pedido->progressoAcompanhamento() }}%"></div>
        </div>

        <ol class="tracking-steps">
          @foreach ($etapas as $i => $etapa)
            <li class="tracking-step {{ $i < $etapaIndice ? 'tracking-step--concluida' : ($i === $etapaIndice ? 'tracking-step--atual' : 'tracking-step--pendente') }}">
              <span class="tracking-step__node">
                {{-- um icone por etapa, na ordem de ETAPAS_ACOMPANHAMENTO:
                     relogio, loja, caixa, caminhao --}}
                @switch($i)
                  @case(0)
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    @break
                  @case(1)
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M4 9h16l-1.4-4.2A1 1 0 0 0 17.6 4H6.4a1 1 0 0 0-1 .8L4 9z"/><path d="M5 9v10h14V9"/></svg>
                    @break
                  @case(2)
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M3 8l9-4 9 4v8l-9 4-9-4V8z"/><path d="M3 8l9 4 9-4"/></svg>
                    @break
                  @default
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M2 7h11v9H2z"/><path d="M13 10h4l3 3v3h-7"/><circle cx="6" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/></svg>
                @endswitch
              </span>
              <span class="tracking-step__label">{{ $etapa['rotulo'] }}</span>
            </li>
          @endforeach
        </ol>
      </div>

      <div class="tracking-card__items">
        <span class="tracking-steps__label">Itens do pedido</span>

        @foreach ($pedido->items as $item)
          <div class="order-card__item">
            <img src="{{ asset($item->product->imagem) }}" alt="{{ $item->product->nome }}">
            <span>{{ $item->product->nome }}{{ $item->tamanho || $item->cor ? ' ('.collect([$item->tamanho, $item->cor])->filter()->implode(' · ').')' : '' }} &times; {{ $item->quantidade }}</span>
            <span>R$ {{ number_format($item->preco_unitario * $item->quantidade, 2, ',', '.') }}</span>
          </div>
        @endforeach
      </div>

      <div class="order-card__footer tracking-card__footer">
        <span>{{ $pedido->tipo_entrega === 'entrega' ? 'Entrega: '.$pedido->endereco_entrega : 'Retirada na loja' }}</span>
        <span class="order-card__total">Total: R$ {{ number_format($pedido->total, 2, ',', '.') }}</span>
      </div>
    </article>
  @empty
    <p class="orders-block__empty">Nenhum pedido em andamento. Quando você comprar, o acompanhamento aparece aqui.</p>
  @endforelse
</section>
@endsection
