(function () {
  const STATS_URL = window.ADMIN_STATS_URL;
  const POLL_INTERVAL_MS = 1000;

  const statKeys = [
    'totalVisitas',
    'visitasHoje',
    'visitantesUnicos',
    'totalCadastrados',
    'cadastrosHoje',
    'totalPedidos',
  ];

  function abreviarNumero(valor) {
    const numero = Number(valor) || 0;

    if (numero >= 1000000) return formatarComSufixo(numero / 1000000) + 'M';
    if (numero >= 1000) {
      if (Math.round((numero / 1000) * 10) / 10 >= 1000) return formatarComSufixo(numero / 1000000) + 'M';
      return formatarComSufixo(numero / 1000) + 'K';
    }
    return String(Math.trunc(numero));
  }

  function formatarComSufixo(valor) {
    const arredondado = Math.round(valor * 10) / 10;
    return String(arredondado).replace('.', ',');
  }

  function updateStatTile(key, value) {
    const el = document.getElementById('stat-' + key);
    if (!el) return;
    const texto = abreviarNumero(value);
    if (el.textContent !== texto) {
      el.textContent = texto;
      el.title = value;
      el.classList.remove('is-pulsing');
      void el.offsetWidth;
      el.classList.add('is-pulsing');
    }
  }

  function renderTableBody(id, rows, renderRow, emptyColspan, emptyText) {
    const tbody = document.getElementById(id);
    if (!tbody) return;

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="${emptyColspan}" class="admin-table__empty">${emptyText}</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(renderRow).join('');
  }

  function renderRankList(id, rows, emptyText) {
    const list = document.getElementById(id);
    if (!list) return;

    if (!rows.length) {
      list.innerHTML = `<li class="admin-table__empty">${emptyText}</li>`;
      return;
    }

    list.innerHTML = rows
      .map((row) => `<li><span>${row.label}</span><span class="admin-rank__count" title="${row.count}">${abreviarNumero(row.count)}</span></li>`)
      .join('');
  }

  function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  }

  async function pollStats() {
    if (!document.getElementById('stat-totalVisitas')) {
      stopPolling();
      return;
    }

    try {
      const response = await fetch(STATS_URL, { headers: { Accept: 'application/json' } });
      if (!response.ok) return;

      const data = await response.json();

      statKeys.forEach((key) => updateStatTile(key, data[key]));

      renderTableBody(
        'tbody-ultimosCadastros',
        data.ultimosCadastros,
        (c) => `<tr><td>${escapeHtml(c.nome)}</td><td>${escapeHtml(c.email)}</td><td>${escapeHtml(c.data)}</td></tr>`,
        3,
        'Nenhum cadastro ainda.'
      );

      renderTableBody(
        'tbody-ultimasVisitas',
        data.ultimasVisitas,
        (v) =>
          `<tr><td>${escapeHtml(v.caminho)}</td><td>${escapeHtml(v.ip)}</td><td>${escapeHtml(v.usuario || '—')}</td><td>${escapeHtml(v.data)}</td></tr>`,
        4,
        'Nenhuma visita registrada ainda.'
      );

      renderRankList(
        'list-termosMaisBuscados',
        data.termosMaisBuscados.map((t) => ({ label: t.termo, count: t.total })),
        'Nenhuma busca registrada ainda.'
      );

      renderRankList(
        'list-produtosMaisVendidos',
        data.produtosMaisVendidos.map((p) => ({ label: p.nome, count: p.total_vendido })),
        'Nenhuma venda registrada ainda.'
      );
    } catch (e) {
      // silencia falhas pontuais de rede e tenta novamente no próximo tick
    }
  }

  function stopPolling() {
    if (window.__adminStatsInterval) {
      clearInterval(window.__adminStatsInterval);
      window.__adminStatsInterval = null;
    }
  }

  stopPolling();
  pollStats();
  window.__adminStatsInterval = setInterval(pollStats, POLL_INTERVAL_MS);
})();
