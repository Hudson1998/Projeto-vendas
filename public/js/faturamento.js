(function () {
  const FATURAMENTO_URL = window.ADMIN_FATURAMENTO_URL;
  const FATURAMENTO_POLL_MS = 1000;

  const formatadorMoeda = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

  const camposMoeda = [
    'caixa',
    'faturamento',
    'entradaHoje',
    'saidaHoje',
    'ganhosDiarios',
    'ganhosMensais',
    'ganhosAnuais',
    'custoProdutos',
    'custoLogistica',
    'projecaoAnual',
  ];

  function updateFaturamentoTile(id, texto) {
    const el = document.getElementById('fat-' + id);
    if (!el) return;
    if (el.textContent !== texto) {
      el.textContent = texto;
      el.classList.remove('is-pulsing');
      void el.offsetWidth;
      el.classList.add('is-pulsing');
    }
  }

  async function pollFaturamento() {
    if (!document.getElementById('fat-caixa')) {
      stopPolling();
      return;
    }

    try {
      const response = await fetch(FATURAMENTO_URL, { headers: { Accept: 'application/json' } });
      if (!response.ok) return;

      const data = await response.json();

      camposMoeda.forEach((campo) => {
        updateFaturamentoTile(campo, formatadorMoeda.format(data[campo] ?? 0));
      });

      updateFaturamentoTile(
        'margemLucro',
        (data.margemLucro ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%'
      );
    } catch (e) {
      // silencia falhas pontuais de rede e tenta novamente no próximo tick
    }
  }

  function stopPolling() {
    if (window.__faturamentoInterval) {
      clearInterval(window.__faturamentoInterval);
      window.__faturamentoInterval = null;
    }
  }

  stopPolling();
  pollFaturamento();
  window.__faturamentoInterval = setInterval(pollFaturamento, FATURAMENTO_POLL_MS);
})();
