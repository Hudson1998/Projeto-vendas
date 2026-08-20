(function () {
  function renderBarChart(containerId, dados, formatador) {
    const container = document.getElementById(containerId);
    if (!container || !dados || !dados.length) return;

    const largura = 600;
    const altura = 180;
    const maxValor = Math.max(1, ...dados.map((d) => d.total));
    const larguraBarra = largura / dados.length;

    const barras = dados
      .map((d, i) => {
        const alturaBarra = (d.total / maxValor) * (altura - 30);
        const x = i * larguraBarra + 4;
        const y = altura - alturaBarra - 20;
        const diaLabel = d.dia.slice(5).replace('-', '/');
        return `
          <g>
            <rect x="${x}" y="${y}" width="${larguraBarra - 8}" height="${alturaBarra}" fill="#e0748a" rx="2"></rect>
            <text x="${x + (larguraBarra - 8) / 2}" y="${altura - 4}" text-anchor="middle" font-size="9" fill="#a8a8ae">${diaLabel}</text>
          </g>`;
      })
      .join('');

    container.innerHTML = `<svg viewBox="0 0 ${largura} ${altura}" width="100%" height="${altura}">${barras}</svg>`;
  }

  function initLojaCharts() {
    if (window.LOJA_VENDAS_POR_DIA) {
      renderBarChart('grafico-vendas', window.LOJA_VENDAS_POR_DIA);
    }
    if (window.LOJA_VISITAS_POR_DIA) {
      renderBarChart('grafico-visitas', window.LOJA_VISITAS_POR_DIA);
    }
  }

  if (window.registerPageInit) {
    window.registerPageInit(initLojaCharts);
  } else {
    initLojaCharts();
  }
})();
