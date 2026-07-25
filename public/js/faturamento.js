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
    'custoLogistica',
    'projecaoAnual',
  ];

  function escalar(numero) {
    if (numero >= 1000000) return { escalado: numero / 1000000, sufixo: 'M' };
    if (numero >= 1000) {
      if (Math.round((numero / 1000) * 10) / 10 >= 1000) return { escalado: numero / 1000000, sufixo: 'M' };
      return { escalado: numero / 1000, sufixo: 'K' };
    }
    return { escalado: numero, sufixo: '' };
  }

  function formatarComSufixo(valor) {
    const arredondado = Math.round(valor * 10) / 10;
    return String(arredondado).replace('.', ',');
  }

  function abreviarMoeda(valor) {
    const numero = Number(valor) || 0;
    const { escalado, sufixo } = escalar(numero);
    if (!sufixo) return formatadorMoeda.format(numero);
    return 'R$ ' + formatarComSufixo(escalado) + sufixo;
  }

  function updateFaturamentoTile(id, texto, tituloCompleto) {
    const el = document.getElementById('fat-' + id);
    if (!el) return;
    if (el.textContent !== texto) {
      el.textContent = texto;
      if (tituloCompleto !== undefined) el.title = tituloCompleto;
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
        const valor = data[campo] ?? 0;
        updateFaturamentoTile(campo, abreviarMoeda(valor), formatadorMoeda.format(valor));
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
