@extends('layouts.loja')

@section('title', 'Dashboard · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Olá, {{ $loja->nomeExibicao() }}</h1>
    <span class="admin-live-indicator"><span class="admin-live-dot"></span> Atualizando automaticamente</span>
  </div>

  <div class="stat-grid">
    <div class="stat-tile">
      <span class="stat-tile__label">Visitantes únicos</span>
      <span class="stat-tile__value">{{ $totalVisitantes }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Peças no catálogo</span>
      <span class="stat-tile__value">{{ $totalProdutos }}</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Pedidos no fluxo</span>
      <span class="stat-tile__value">{{ $pedidosNoFluxo }}</span>
    </div>
    <div class="stat-tile stat-tile--destaque">
      <span class="stat-tile__label">Saldo disponível</span>
      <span class="stat-tile__value">R$ {{ number_format($carteira['disponivel'], 2, ',', '.') }}</span>
      <span class="stat-tile__hint">A receber: R$ {{ number_format($carteira['a_receber'], 2, ',', '.') }}</span>
    </div>
  </div>

  {{-- os graficos sao o mesmo app Angular do painel do admin; este seletor
       monta a variante da loja, que le os endpoints de /loja/graficos --}}
  <app-loja-root></app-loja-root>

  <div class="admin-panel admin-panel--largo" style="margin-top: 24px;">
    <h2 class="admin-panel__title">Vendas</h2>
    <table class="admin-table">
      <thead>
        <tr><th>Pedido</th><th>Data</th><th>Cliente</th><th>Peça</th><th>Qtd.</th><th>Bruto</th><th>Líquido</th><th>Situação</th></tr>
      </thead>
      <tbody>
        @forelse ($vendas as $v)
          <tr>
            <td>#{{ $v['pedido_id'] }}</td>
            <td>{{ \Carbon\Carbon::parse($v['data'])->format('d/m/Y') }}</td>
            <td>{{ $v['cliente'] }}</td>
            <td>{{ $v['produto'] }}</td>
            <td>{{ $v['quantidade'] }}</td>
            <td>R$ {{ number_format($v['bruto'], 2, ',', '.') }}</td>
            <td>R$ {{ number_format($v['liquido'], 2, ',', '.') }}</td>
            <td>{{ $v['status_separacao'] ?? ($v['status_pagamento'] === 'aprovado' ? 'aguardando loja' : 'pagamento em análise') }}</td>
          </tr>
        @empty
          <tr><td colspan="8" class="admin-table__empty">Nenhuma venda registrada ainda.</td></tr>
        @endforelse
      </tbody>
    </table>
    <p class="carteira-aviso">Líquido = bruto menos a comissão da plataforma ({{ rtrim(rtrim(number_format($carteira['taxa'], 1, ',', '.'), '0'), ',') }}%).</p>
  </div>
</div>

<link rel="stylesheet" href="{{ asset('admin-charts/browser/styles.css') }}">
<script src="{{ asset('admin-charts/browser/main.js') }}" type="module"></script>
@endsection
