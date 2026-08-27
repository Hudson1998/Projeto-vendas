@extends('layouts.loja')

@section('title', 'Carteira · Painel da Loja')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Carteira</h1>
  </div>

  @if (session('status'))
    <div class="form-status">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="form-status form-status--error">
      @foreach ($errors->all() as $erro)
        <p>{{ $erro }}</p>
      @endforeach
    </div>
  @endif

  <div class="stat-grid">
    <div class="stat-tile stat-tile--destaque">
      <span class="stat-tile__label">Saldo disponível</span>
      <span class="stat-tile__value">R$ {{ number_format($carteira['disponivel'], 2, ',', '.') }}</span>
      <span class="stat-tile__hint">Pedidos já entregues, prontos para saque.</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Saldo a receber</span>
      <span class="stat-tile__value">R$ {{ number_format($carteira['a_receber'], 2, ',', '.') }}</span>
      <span class="stat-tile__hint">Pedidos pagos que ainda não chegaram ao cliente.</span>
    </div>
    <div class="stat-tile">
      <span class="stat-tile__label">Já sacado</span>
      <span class="stat-tile__value">R$ {{ number_format($carteira['sacado'], 2, ',', '.') }}</span>
    </div>
  </div>

  <div class="admin-grid">
    <div class="admin-panel">
      <h2 class="admin-panel__title">Sacar</h2>

      <form method="POST" action="{{ route('loja.carteira.sacar') }}" class="carteira-form">
        @csrf
        <div class="field">
          <label for="valor">Valor do saque</label>
          <input type="number" id="valor" name="valor" step="0.01" min="0.01"
                 max="{{ $carteira['disponivel'] }}" value="{{ old('valor') }}"
                 placeholder="0,00" @disabled($carteira['disponivel'] <= 0) required>
          <span class="field__hint">Disponível: R$ {{ number_format($carteira['disponivel'], 2, ',', '.') }}</span>
        </div>
        <button type="submit" class="btn btn-primary btn-block" @disabled($carteira['disponivel'] <= 0)>
          Solicitar saque
        </button>
      </form>

      {{-- SIMULACAO: nao ha repasse bancario. O saque so registra a intencao e
           desconta do saldo; sai junto com o gateway de pagamento real. --}}
      <p class="carteira-aviso">
        Sem integração bancária ainda: o saque fica registrado como solicitado e
        já desconta do saldo disponível.
      </p>
    </div>

    <div class="admin-panel">
      <h2 class="admin-panel__title">Como o saldo é formado</h2>
      <table class="admin-table">
        <tbody>
          <tr><td>Vendas brutas</td><td>R$ {{ number_format($carteira['bruto_total'], 2, ',', '.') }}</td></tr>
          <tr><td>Comissão da plataforma ({{ rtrim(rtrim(number_format($carteira['taxa'], 1, ',', '.'), '0'), ',') }}%)</td>
              <td>− R$ {{ number_format($carteira['comissao'], 2, ',', '.') }}</td></tr>
          <tr><td>Saques já solicitados</td><td>− R$ {{ number_format($carteira['sacado'], 2, ',', '.') }}</td></tr>
          <tr class="admin-table__total">
            <td>Disponível + a receber</td>
            <td>R$ {{ number_format($carteira['disponivel'] + $carteira['a_receber'], 2, ',', '.') }}</td>
          </tr>
        </tbody>
      </table>
      <p class="carteira-aviso">O frete não entra: ele paga a entrega, não a loja.</p>
    </div>

    <div class="admin-panel admin-panel--largo">
      <h2 class="admin-panel__title">Extrato de saques</h2>
      <table class="admin-table">
        <thead><tr><th>Data</th><th>Valor</th><th>Destino</th><th>Situação</th></tr></thead>
        <tbody>
          @forelse ($saques as $saque)
            <tr>
              <td>{{ $saque->created_at->format('d/m/Y H:i') }}</td>
              <td>R$ {{ number_format($saque->valor, 2, ',', '.') }}</td>
              <td>{{ $saque->destino ?? '—' }}</td>
              <td>{{ ucfirst($saque->status) }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="admin-table__empty">Nenhum saque solicitado ainda.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
