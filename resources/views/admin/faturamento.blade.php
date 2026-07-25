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
  </div>
</div>

<script>
  window.ADMIN_FATURAMENTO_URL = "{{ route('admin.faturamento.dados') }}";
</script>
<script src="{{ asset('js/faturamento.js') }}"></script>
@endsection
