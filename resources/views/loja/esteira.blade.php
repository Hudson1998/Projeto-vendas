@extends('layouts.loja')

@section('title', 'Esteira de pedidos · Painel da Loja')

@section('content')
@php
  // cada coluna sabe qual acao a empurra para a seguinte
  $acoes = [
    'aprovar' => ['rota' => 'loja.esteira.aceitar', 'texto' => 'Aceitar pedido'],
    'separar' => ['rota' => 'loja.esteira.separar', 'texto' => 'Marcar como separado'],
    'transporte' => ['rota' => 'loja.esteira.despachar', 'texto' => 'Entregar ao transporte'],
    'caminho' => ['rota' => 'loja.esteira.entregar', 'texto' => 'Confirmar entrega'],
  ];
  // as duas etapas que emitem documento, para o aviso na coluna
  $emiteDocumento = ['aprovar', 'transporte', 'caminho'];
@endphp

<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Esteira de pedidos</h1>
    <span class="admin-live-indicator">{{ $pedidos->flatten()->count() }} pedidos no fluxo</span>
  </div>

  @if (session('status'))
    <div class="form-status">{{ session('status') }}</div>
  @endif

  <div class="esteira">
    @foreach ($colunas as $chave => $coluna)
      @php($daColuna = $pedidos[$chave] ?? collect())
      <section class="esteira__coluna" data-coluna="{{ $chave }}">
        <header class="esteira__cabecalho">
          <span class="esteira__titulo">{{ $coluna['rotulo'] }}</span>
          <span class="esteira__contador">{{ $daColuna->count() }}</span>
        </header>
        <p class="esteira__ajuda">{{ $coluna['ajuda'] }}</p>

        <div class="esteira__cartoes">
          @forelse ($daColuna as $pedido)
            <article class="esteira-card">
              <div class="esteira-card__topo">
                <span class="esteira-card__id">#{{ $pedido->id }}</span>
                <span class="esteira-card__data">{{ $pedido->created_at->format('d/m H:i') }}</span>
              </div>

              <span class="esteira-card__cliente">{{ $pedido->user?->name ?? 'Cliente removido' }}</span>

              <ul class="esteira-card__itens">
                @foreach ($pedido->items as $item)
                  <li>
                    {{ $item->quantidade }}× {{ $item->product?->nome ?? 'Peça removida' }}
                    @if ($item->tamanho || $item->cor)
                      <span class="esteira-card__variante">{{ collect([$item->tamanho, $item->cor])->filter()->implode(' · ') }}</span>
                    @endif
                  </li>
                @endforeach
              </ul>

              <div class="esteira-card__rodape">
                <span class="esteira-card__total">R$ {{ number_format($pedido->total, 2, ',', '.') }}</span>
                <span class="esteira-card__entrega">
                  {{ $pedido->tipo_entrega === 'entrega'
                      ? ($pedido->entrega_propria ? 'Entrega própria' : ($pedido->transportadora?->nome ?? 'Transporte a definir'))
                      : 'Retirada na loja' }}
                </span>
              </div>

              @if ($pedido->status_pagamento === 'aguardando_analise')
                <span class="esteira-card__alerta">Pagamento ainda em conferência</span>
              @endif

              <form method="POST" action="{{ route($acoes[$chave]['rota'], $pedido) }}">
                @csrf
                <button type="submit" class="btn btn-primary btn-block esteira-card__acao">
                  {{ $acoes[$chave]['texto'] }}
                </button>
              </form>

              @if (in_array($chave, $emiteDocumento, true))
                <span class="esteira-card__documento">Emite documento</span>
              @endif
            </article>
          @empty
            <p class="esteira__vazio">Nada aqui.</p>
          @endforelse
        </div>
      </section>
    @endforeach
  </div>

  @php($entregues = $pedidos['entregue'] ?? collect())
  @if ($entregues->isNotEmpty())
    <div class="admin-panel" style="margin-top: 24px;">
      <h2 class="admin-panel__title">Entregues ({{ $entregues->count() }})</h2>
      <table class="admin-table">
        <thead><tr><th>Pedido</th><th>Cliente</th><th>Total</th><th>Documentos</th></tr></thead>
        <tbody>
          @foreach ($entregues->take(10) as $pedido)
            <tr>
              <td>#{{ $pedido->id }}</td>
              <td>{{ $pedido->user?->name ?? '—' }}</td>
              <td>R$ {{ number_format($pedido->total, 2, ',', '.') }}</td>
              <td><a href="{{ route('loja.documentos') }}" class="link-btn">Ver documentos</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
