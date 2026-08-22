(function () {
  const STATS_URL = window.ADMIN_STATS_URL;

  // 1s empilhava requisicao: o servidor de desenvolvimento atende uma por vez,
  // e o setInterval nao esperava a anterior terminar. Com a aba aberta um tempo
  // a fila so crescia e o painel inteiro travava. 5s da folga de sobra para um
  // painel que mostra contadores.
  const POLL_INTERVAL_MS = 5000;

  // Teto do recuo progressivo quando o servidor falha ou demora.
  const POLL_INTERVAL_MAX_MS = 60000;

  // Requisicao que passa disso e considerada perdida e e abortada, senao ela
  // seguraria a fila do servidor sozinha.
  const REQUEST_TIMEOUT_MS = 15000;

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
    const controller = new AbortController();
    const expirar = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

    try {
      const response = await fetch(STATS_URL, {
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      });
      if (!response.ok) return false;

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

      return true;
    } catch (e) {
      // silencia falhas pontuais de rede e tenta novamente no próximo tick
      return false;
    } finally {
      clearTimeout(expirar);
    }
  }

  /* Ciclo de atualizacao.

     Em vez de setInterval, cada rodada agenda a proxima so depois de terminar.
     Assim nunca existe mais de uma requisicao em voo: se o servidor demorar, o
     ciclo espera, em vez de abrir outra por cima e formar fila. */
  let timer = null;
  let intervaloAtual = POLL_INTERVAL_MS;
  let rodando = false;

  function agendar(atraso) {
    clearTimeout(timer);
    timer = setTimeout(ciclo, atraso);
  }

  async function ciclo() {
    // a tabela sumiu do DOM (navegou para outra pagina do painel): encerra
    if (!document.getElementById('stat-totalVisitas')) {
      parar();
      return;
    }

    // aba em segundo plano nao precisa de dado fresco; volta a atualizar no
    // visibilitychange, que ja dispara uma rodada imediata
    if (document.hidden) {
      agendar(POLL_INTERVAL_MS);
      return;
    }

    if (rodando) {
      agendar(intervaloAtual);
      return;
    }

    rodando = true;
    const ok = await pollStats();
    rodando = false;

    // servidor falhando: espaca as tentativas ate o teto em vez de insistir no
    // mesmo ritmo, que so piora a fila de quem ja esta sobrecarregado
    intervaloAtual = ok
      ? POLL_INTERVAL_MS
      : Math.min(intervaloAtual * 2, POLL_INTERVAL_MAX_MS);

    agendar(intervaloAtual);
  }

  function parar() {
    clearTimeout(timer);
    timer = null;
    document.removeEventListener('visibilitychange', aoMudarVisibilidade);
    window.__adminStatsPolling = false;
  }

  function aoMudarVisibilidade() {
    if (document.hidden) return;
    // voltou para a aba: mostra dado atual sem esperar o proximo tick
    intervaloAtual = POLL_INTERVAL_MS;
    agendar(0);
  }

  if (!window.__adminStatsPolling) {
    window.__adminStatsPolling = true;
    document.addEventListener('visibilitychange', aoMudarVisibilidade);
    ciclo();
  }
})();
