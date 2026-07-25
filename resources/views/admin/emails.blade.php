@extends('layouts.admin')

@section('title', 'Emails · Painel HR')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Emails</h1>
  </div>

  <div class="chart-panel">
    <div class="admin-panel__actions">
      <h2 class="admin-panel__title">Copiar todos os e-mails</h2>
      <button type="button" class="btn btn-outline" id="copiar-emails-btn" style="padding: 10px 20px;">Copiar</button>
    </div>
    <textarea class="email-copy-textarea" id="lista-emails" readonly>{{ $clientes->pluck('email')->implode(', ') }}</textarea>
  </div>

  <div class="admin-panel admin-panel--full">
    <table class="admin-table">
      <thead>
        <tr><th>Nome</th><th>E-mail</th></tr>
      </thead>
      <tbody>
        @forelse ($clientes as $cliente)
          <tr>
            <td><strong>{{ $cliente->name }}</strong></td>
            <td><a href="mailto:{{ $cliente->email }}">{{ $cliente->email }}</a></td>
          </tr>
        @empty
          <tr><td colspan="2" class="admin-table__empty">Nenhum e-mail cadastrado ainda.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById('copiar-emails-btn')?.addEventListener('click', function () {
    const textarea = document.getElementById('lista-emails');
    textarea.select();
    navigator.clipboard.writeText(textarea.value).then(() => {
      const btn = document.getElementById('copiar-emails-btn');
      const original = btn.textContent;
      btn.textContent = 'Copiado!';
      setTimeout(() => (btn.textContent = original), 1800);
    });
  });
</script>
@endsection
