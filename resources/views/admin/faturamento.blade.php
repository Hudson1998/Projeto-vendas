@extends('layouts.admin')

@section('title', 'Faturamento · Painel HR')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Faturamento</h1>
    <span class="admin-live-indicator"><span class="admin-live-dot"></span> Atualizando a cada segundo</span>
  </div>

  @if (session('status'))
    <div class="form-status">{{ session('status') }}</div>
  @endif

  <div class="chart-panel">
    <div class="admin-panel__actions">
      <h2 class="admin-panel__title">Custo logístico padrão por pedido</h2>
    </div>
    <p class="faturamento-hint">Usado para calcular saída, ganhos e margem. Some 0 se ainda não tiver esse custo definido.</p>
    <form method="POST" action="{{ route('admin.faturamento.config') }}" class="faturamento-config-form">
      @csrf
      <span class="faturamento-config-form__prefix">R$</span>
      <input type="number" name="custo_logistica_padrao" step="0.01" min="0" value="{{ $custoLogisticaPadrao }}">
      <button type="submit" class="btn btn-primary" style="padding: 11px 24px;">Salvar</button>
    </form>
  </div>

  <div class="stat-grid stat-grid--compact">
    <div class="stat-tile">
      <span class="stat-tile__label">Caixa (produtos vendidos)</span>
      <span class="stat-tile__value" id="fat-caixa">R$ {{ number_format($caixa, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Faturamento do site</span>
      <span class="stat-tile__value" id="fat-faturamento">R$ {{ number_format($faturamento, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Entrada hoje</span>
      <span class="stat-tile__value" id="fat-entradaHoje">R$ {{ number_format($entradaHoje, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Saída hoje</span>
      <span class="stat-tile__value stat-tile__value--negative" id="fat-saidaHoje">R$ {{ number_format($saidaHoje, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Ganhos diários</span>
      <span class="stat-tile__value" id="fat-ganhosDiarios">R$ {{ number_format($ganhosDiarios, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Ganhos mensais</span>
      <span class="stat-tile__value" id="fat-ganhosMensais">R$ {{ number_format($ganhosMensais, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Ganhos anuais</span>
      <span class="stat-tile__value" id="fat-ganhosAnuais">R$ {{ number_format($ganhosAnuais, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Margem de lucro</span>
      <span class="stat-tile__value" id="fat-margemLucro">{{ number_format($margemLucro, 1, ',', '.') }}%</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Gasto em produtos</span>
      <span class="stat-tile__value stat-tile__value--negative" id="fat-custoProdutos">R$ {{ number_format($custoProdutos, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Gasto em logística</span>
      <span class="stat-tile__value stat-tile__value--negative" id="fat-custoLogistica">R$ {{ number_format($custoLogistica, 2, ',', '.') }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Projeção anual</span>
      <span class="stat-tile__value" id="fat-projecaoAnual">R$ {{ number_format($projecaoAnual, 2, ',', '.') }}</span>
    </div>
  </div>

  <p class="faturamento-hint">Projeção anual é uma estimativa com base no ritmo médio de ganhos desde o primeiro pedido — não é garantia de resultado futuro. Gastos em produtos e logística dependem do custo cadastrado em cada peça e do custo logístico padrão acima.</p>
</div>

<script>
  window.ADMIN_FATURAMENTO_URL = "{{ route('admin.faturamento.dados') }}";
</script>
<script src="{{ asset('js/faturamento.js') }}"></script>
@endsection
