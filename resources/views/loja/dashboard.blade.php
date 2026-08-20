@extends('layouts.loja')

@section('title', 'Dashboard · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Olá, {{ $loja->nome_fantasia }}</h1>
  </div>

  <div class="stat-grid">
    <div class="stat-tile">
      <span class="stat-tile__label">Visitantes únicos</span>
      <span class="stat-tile__value">{{ $totalVisitantes }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Vendas (14 dias)</span>
      <span class="stat-tile__value">R$ {{ number_format($vendasPorDia->sum('total'), 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Visitas aos produtos (14 dias)</span>
      <span class="stat-tile__value">{{ $visitasPorDia->sum('total') }}</span>
    </div>
  </div>

  <div class="admin-grid">
    <div class="admin-panel">
      <h2 class="admin-panel__title">Vendas por dia</h2>
      <div class="loja-chart" id="grafico-vendas"></div>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Visitas por dia</h2>
      <div class="loja-chart" id="grafico-visitas"></div>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Produtos mais vendidos</h2>
      <ul class="admin-rank">
        @forelse ($produtosMaisVendidos as $p)
          <li><span>{{ $p->nome }}</span><span class="admin-rank__count">{{ $p->quantidade_vendida }}</span></li>
        @empty
          <li class="admin-table__empty">Nenhuma venda registrada ainda.</li>
        @endforelse
      </ul>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Produtos mais visitados</h2>
      <ul class="admin-rank">
        @forelse ($produtosMaisVisitados as $p)
          <li><span>{{ $p->nome }}</span></li>
        @empty
          <li class="admin-table__empty">Nenhuma visita registrada ainda.</li>
        @endforelse
      </ul>
    </div>
  </div>
</div>

<script>
  window.LOJA_VENDAS_POR_DIA = @json($vendasPorDia);
  window.LOJA_VISITAS_POR_DIA = @json($visitasPorDia);
</script>
@endsection
