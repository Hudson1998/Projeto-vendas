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

  <div class="stat-grid stat-grid--compact">
    <div class="stat-tile">
      <span class="stat-tile__label">Caixa (produtos vendidos)</span>
      <span class="stat-tile__value" id="fat-caixa" title="R$ {{ number_format($caixa, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($caixa) }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Faturamento do site</span>
      <span class="stat-tile__value" id="fat-faturamento" title="R$ {{ number_format($faturamento, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($faturamento) }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Entrada hoje</span>
      <span class="stat-tile__value" id="fat-entradaHoje" title="R$ {{ number_format($entradaHoje, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($entradaHoje) }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Saída hoje</span>
      <span class="stat-tile__value stat-tile__value--negative" id="fat-saidaHoje" title="R$ {{ number_format($saidaHoje, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($saidaHoje) }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Ganhos diários</span>
      <span class="stat-tile__value" id="fat-ganhosDiarios" title="R$ {{ number_format($ganhosDiarios, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($ganhosDiarios) }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Ganhos mensais</span>
      <span class="stat-tile__value" id="fat-ganhosMensais" title="R$ {{ number_format($ganhosMensais, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($ganhosMensais) }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Ganhos anuais</span>
      <span class="stat-tile__value" id="fat-ganhosAnuais" title="R$ {{ number_format($ganhosAnuais, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($ganhosAnuais) }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Margem de lucro</span>
      <span class="stat-tile__value" id="fat-margemLucro">{{ number_format($margemLucro, 1, ',', '.') }}%</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Gasto em logística</span>
      <span class="stat-tile__value stat-tile__value--negative" id="fat-custoLogistica" title="R$ {{ number_format($custoLogistica, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($custoLogistica) }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Projeção anual</span>
      <span class="stat-tile__value" id="fat-projecaoAnual" title="R$ {{ number_format($projecaoAnual, 2, ',', '.') }}">{{ \App\Support\NumberAbbreviator::abreviarMoeda($projecaoAnual) }}</span>
    </div>
  </div>

  <p class="faturamento-hint">Projeção anual é uma estimativa com base no ritmo médio de ganhos desde o primeiro pedido — não é garantia de resultado futuro. Gastos em logística dependem do custo logístico padrão configurado no sistema.</p>
</div>

<script>
  window.ADMIN_FATURAMENTO_URL = "{{ route('admin.faturamento.dados') }}";
</script>
<script src="{{ asset('js/faturamento.js') }}"></script>
@endsection
