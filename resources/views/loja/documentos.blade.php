@extends('layouts.loja')

@section('title', 'Documentos · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Documentos dos pedidos</h1>
    <span class="admin-live-indicator">{{ count($documentos) }} documentos emitidos</span>
  </div>

  <p class="admin-panel__ajuda">
    Cada etapa em que alguém assume a mercadoria emite um documento:
    {{ implode(', ', array_values($rotulos)) }}.
  </p>

  @if (empty($documentos))
    <div class="admin-panel">
      <p class="admin-table__empty">
        Nenhum documento ainda. Eles são emitidos automaticamente quando você aceita
        um pedido, entrega ao transporte e confirma a entrega ao cliente na
        <a href="{{ route('loja.esteira') }}" class="link-btn">esteira de pedidos</a>.
      </p>
    </div>
  @else
    <div class="documentos">
      @foreach ($documentos as $doc)
        @php($d = $doc['dados'])
        <article class="documento" data-etapa="{{ $doc['etapa'] }}">
          <header class="documento__topo">
            <span class="documento__etapa">{{ $doc['rotulo'] }}</span>
            <span class="documento__pedido">Pedido #{{ $doc['order_id'] }}</span>
          </header>

          <dl class="documento__campos">
            <div>
              <dt>Emitido em</dt>
              <dd>{{ isset($d['emitido_em']) ? \Carbon\Carbon::parse($d['emitido_em'])->format('d/m/Y H:i') : '—' }}</dd>
            </div>
            <div>
              <dt>Cliente</dt>
              <dd>{{ $d['cliente']['nome'] ?? '—' }}</dd>
            </div>
            <div>
              <dt>Peças</dt>
              <dd>{{ collect($d['itens'] ?? [])->sum('quantidade') }} un.</dd>
            </div>
            <div>
              <dt>Valor</dt>
              <dd>R$ {{ number_format($d['pedido']['total_pecas'] ?? 0, 2, ',', '.') }}</dd>
            </div>

            @if ($doc['etapa'] === \App\Support\DocumentoDePedido::TRANSPORTE)
              <div>
                <dt>Responsável</dt>
                <dd>{{ $d['responsavel'] ?? '—' }}</dd>
              </div>
              <div>
                <dt>Volume</dt>
                <dd>{{ $d['volume']['dimensoes'] ?? 'não informado' }}{{ ($d['volume']['fragil'] ?? false) ? ' · frágil' : '' }}</dd>
              </div>
            @endif

            @if ($doc['etapa'] === \App\Support\DocumentoDePedido::ENTREGA)
              <div class="documento__campo--largo">
                <dt>Endereço</dt>
                <dd>{{ $d['endereco'] ?? 'Retirada na loja' }}</dd>
              </div>
            @endif
          </dl>

          <details class="documento__bruto">
            <summary>Ver documento completo</summary>
            <pre>{{ json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
          </details>
        </article>
      @endforeach
    </div>
  @endif
</div>
@endsection
