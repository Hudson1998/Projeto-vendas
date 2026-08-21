(function () {
  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
  }

  function mostrarToast(mensagem, tipo) {
    const toast = document.createElement('div');
    toast.className = 'toast' + (tipo === 'erro' ? ' toast--erro' : '');
    toast.textContent = mensagem;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    setTimeout(() => {
      toast.classList.remove('is-visible');
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  function atualizarBadgeCarrinho(quantidade) {
    const badge = document.getElementById('cart-badge');
    if (!badge) return;
    badge.textContent = quantidade;
    badge.style.display = quantidade > 0 ? '' : 'none';
  }

  function confirmarAcao({ titulo, mensagem, textoConfirmar, textoCancelar, onConfirm }) {
    const confirmModal = document.getElementById('confirm-modal');
    if (!confirmModal) {
      onConfirm();
      return;
    }

    confirmModal.querySelector('#confirm-modal-title').textContent = titulo || 'Confirmar';
    confirmModal.querySelector('#confirm-modal-text').textContent = mensagem || 'Tem certeza?';
    confirmModal.querySelector('#confirm-modal-confirm').textContent = textoConfirmar || 'Confirmar';
    confirmModal.querySelector('#confirm-modal-cancel').textContent = textoCancelar || 'Cancelar';

    const botaoConfirmar = confirmModal.querySelector('#confirm-modal-confirm');
    const botaoCancelar = confirmModal.querySelector('#confirm-modal-cancel');

    function abrir() {
      confirmModal.classList.add('is-open');
      confirmModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
    }

    function fechar() {
      confirmModal.classList.remove('is-open');
      confirmModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
      botaoConfirmar.removeEventListener('click', aoConfirmar);
      botaoCancelar.removeEventListener('click', fechar);
      confirmModal.removeEventListener('click', aoClicarFora);
      document.removeEventListener('keydown', aoPressionarEsc);
    }

    function aoConfirmar() {
      fechar();
      onConfirm();
    }

    function aoClicarFora(e) {
      if (e.target === confirmModal) fechar();
    }

    function aoPressionarEsc(e) {
      if (e.key === 'Escape') fechar();
    }

    botaoConfirmar.addEventListener('click', aoConfirmar);
    botaoCancelar.addEventListener('click', fechar);
    confirmModal.addEventListener('click', aoClicarFora);
    document.addEventListener('keydown', aoPressionarEsc);

    abrir();
  }

  window.csrfToken = csrfToken;
  window.mostrarToast = mostrarToast;
  window.atualizarBadgeCarrinho = atualizarBadgeCarrinho;
  window.confirmarAcao = confirmarAcao;

  /* Vitrine de produtos (home) — estado reinicia a cada carregamento da página */
  let gridState = null;
  let buscaTimeout = null;
  const PRODUTOS_POR_PAGINA = 40;

  function cartaoProduto(item) {
    const precoHtml = item.precoPromocional
      ? `<span class="product-card__price product-card__price--old">${item.preco}</span><span class="product-card__price product-card__price--promo">${item.precoPromocional}</span>`
      : `<span class="product-card__price">${item.preco}</span>`;

    return `
      <div class="product-card">
        <a href="/produtos/${item.id}" class="product-card__link">
          <div class="product-card__image-wrap">
            <img class="product-card__image" src="${item.url}" alt="${item.nome}">
          </div>
          <div class="product-card__body">
            <span class="product-card__category">${item.categoria}</span>
            <span class="product-card__name">${item.nome}</span>
            ${precoHtml}
          </div>
        </a>
        <button type="button" class="btn-favorite ${item.favoritado ? 'is-active' : ''}" data-id="${item.id}" title="Favoritar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="${item.favoritado ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.6"><path d="M12 21s-7.5-4.6-10-9.1C.4 8.3 2 4.5 5.6 4c2-.3 3.9.6 6.4 3 2.5-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.3 3.6 7.9-2.5 4.5-10 9.1-10 9.1z"/></svg>
        </button>
      </div>`;
  }

  const CARROSSEL_MIN_CARTOES = 6;

  function initCarrossel(chave, produtos) {
    const secao = document.getElementById('secao-' + chave);
    const track = document.getElementById('carousel-' + chave);
    if (!secao || !track) return;

    secao.style.display = '';

    if (!produtos || produtos.length === 0) {
      track.innerHTML = cartaoVazio().repeat(CARROSSEL_MIN_CARTOES);
      return;
    }

    track.innerHTML = produtos.map(cartaoProduto).join('');
  }

  function initCarrosseis() {
    initCarrossel('mais-comprados', window.CARROSSEL_MAIS_COMPRADOS || []);
    initCarrossel('mais-visitados', window.CARROSSEL_MAIS_VISITADOS || []);
    initCarrossel('promocoes', window.CARROSSEL_PROMOCOES || []);
  }

  function cartaoVazio() {
    return `
      <div class="product-card product-card--blank" aria-hidden="true">
        <div class="product-card__image-wrap product-card__image-wrap--blank"></div>
        <div class="product-card__body">
          <span class="product-card__placeholder-line"></span>
          <span class="product-card__placeholder-line product-card__placeholder-line--short"></span>
        </div>
      </div>`;
  }

  function renderPaginacao(totalPaginas) {
    const container = document.getElementById('pagination');
    if (!container) return;

    if (totalPaginas <= 1) {
      container.innerHTML = '';
      return;
    }

    let botoes = '';
    for (let i = 1; i <= totalPaginas; i++) {
      botoes += `<button type="button" class="pagination__page ${i === gridState.pagina ? 'is-active' : ''}" data-pagina="${i}">${i}</button>`;
    }
    container.innerHTML = botoes;
  }

  /* Filtros da vitrine.

     A arvore vem de window.FILTRO_ARVORE, montada em PaginaInicial::arvoreDeFiltros
     a partir das categorias que existem de fato em products.categoria -- por isso
     o painel nunca oferece um caminho que cai em colecao vazia. */
  const FAIXAS_PRECO = [
    { id: 'ate-100', rotulo: 'Até R$ 100', min: 0, max: 100 },
    { id: '100-300', rotulo: 'R$ 100 a 300', min: 100, max: 300 },
    { id: '300-600', rotulo: 'R$ 300 a 600', min: 300, max: 600 },
    { id: 'acima-600', rotulo: 'Acima de R$ 600', min: 600, max: Infinity },
  ];

  const ORDENACOES = [
    { id: 'relevancia', rotulo: 'Relevância' },
    { id: 'mais-vendidos', rotulo: 'Mais vendidos' },
    { id: 'mais-vistos', rotulo: 'Mais vistos' },
    { id: 'menor-preco', rotulo: 'Menor preço' },
    { id: 'maior-preco', rotulo: 'Maior preço' },
  ];

  const ORDEM_TAMANHOS = ['PP', 'P', 'M', 'G', 'GG', 'Único'];

  const ESCAPES_HTML = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  };

  // nomes de categoria vem do cadastro do admin e entram em atributo HTML
  function escapar(valor) {
    return String(valor).replace(/[&<>"']/g, (c) => ESCAPES_HTML[c]);
  }

  function arvoreFiltros() {
    return window.FILTRO_ARVORE || [];
  }

  function familiaAtual() {
    if (!gridState || !gridState.familia) return null;
    return arvoreFiltros().find((f) => f.familia === gridState.familia) || null;
  }

  function ordenarTamanhos(lista) {
    return lista.slice().sort((a, b) => {
      const ia = ORDEM_TAMANHOS.indexOf(a);
      const ib = ORDEM_TAMANHOS.indexOf(b);
      // numero de calcado nao esta na ordem fixa: cai no natural (34 < 35)
      if (ia === -1 && ib === -1) return a.localeCompare(b, 'pt-BR', { numeric: true });
      if (ia === -1) return 1;
      if (ib === -1) return -1;
      return ia - ib;
    });
  }

  function gradeAtual() {
    const familia = familiaAtual();
    if (familia) return familia.grade || [];

    const todas = [];
    arvoreFiltros().forEach((f) => {
      (f.grade || []).forEach((t) => {
        if (todas.indexOf(t) === -1) todas.push(t);
      });
    });
    return ordenarTamanhos(todas);
  }

  function ordenar(lista) {
    if (gridState.ordem === 'mais-vendidos') {
      lista.sort((a, b) => (b.vendas || 0) - (a.vendas || 0));
    } else if (gridState.ordem === 'mais-vistos') {
      lista.sort((a, b) => (b.visualizacoes || 0) - (a.visualizacoes || 0));
    } else if (gridState.ordem === 'menor-preco') {
      lista.sort((a, b) => (a.precoNumerico || 0) - (b.precoNumerico || 0));
    } else if (gridState.ordem === 'maior-preco') {
      lista.sort((a, b) => (b.precoNumerico || 0) - (a.precoNumerico || 0));
    }
    // relevancia mantem a ordem que o servidor mandou (categoria, nome)
  }

  function contarFiltros() {
    let total = 0;
    if (gridState.familia) total++;
    if (gridState.categoria) total++;
    if (gridState.faixa) total++;
    if (gridState.tamanhos.length) total++;
    return total;
  }

  function tituloDaColecao() {
    if (gridState.categoria) return gridState.categoria;
    if (gridState.familia) return gridState.familia;
    return 'Coleção';
  }

  function resumoDosFiltros(total) {
    const partes = [total + (total === 1 ? ' peça' : ' peças')];
    if (gridState.tamanhos.length) partes.push('tam ' + gridState.tamanhos.join('/'));
    const faixa = FAIXAS_PRECO.find((f) => f.id === gridState.faixa);
    if (faixa) partes.push(faixa.rotulo);
    const ordem = ORDENACOES.find((o) => o.id === gridState.ordem);
    if (ordem && ordem.id !== 'relevancia') partes.push(ordem.rotulo.toLowerCase());
    return partes.join(' · ');
  }

  function chipFiltro(rotulo, atributo, valor, ativo, extra) {
    return `<button type="button" class="filter-chip ${extra || ''} ${ativo ? 'is-active' : ''}" ${atributo}="${escapar(valor)}">${escapar(rotulo)}</button>`;
  }

  function montarPainelFiltros() {
    const corpo = document.getElementById('filter-body');
    if (!corpo || !gridState) return;

    const familia = familiaAtual();
    const folhas = familia ? familia.folhas || [] : [];
    const grade = gradeAtual();

    let abas = `<button type="button" class="filter-tab ${!gridState.familia ? 'is-active' : ''}" data-familia="">Todas</button>`;
    arvoreFiltros().forEach((f) => {
      abas += `<button type="button" class="filter-tab ${gridState.familia === f.familia ? 'is-active' : ''}" data-familia="${escapar(f.familia)}">${escapar(f.familia)}</button>`;
    });

    let html = `<div class="filter-section">
        <span class="filter-section__label">Categoria</span>
        <div class="filter-tabs">${abas}</div>`;

    if (folhas.length) {
      html += `<div class="filter-chips">${folhas
        .map((c) => chipFiltro(c, 'data-categoria', c, gridState.categoria === c))
        .join('')}</div>`;
    } else if (familia) {
      html += `<p class="filter-section__vazio">O catálogo ainda não separa ${escapar(familia.familia.toLowerCase())} em subcategorias.</p>`;
    }

    html += `</div>`;

    if (grade.length) {
      html += `<div class="filter-section">
        <span class="filter-section__label">Tamanho <span class="filter-section__hint">pode marcar mais de um</span></span>
        <div class="filter-chips">${grade
          .map((t) => chipFiltro(t, 'data-tamanho', t, gridState.tamanhos.indexOf(t) !== -1, 'filter-chip--tamanho'))
          .join('')}</div>
      </div>`;
    }

    html += `<div class="filter-section">
        <span class="filter-section__label">Preço</span>
        <div class="filter-chips">${FAIXAS_PRECO
          .map((f) => chipFiltro(f.rotulo, 'data-faixa', f.id, gridState.faixa === f.id))
          .join('')}</div>
      </div>
      <div class="filter-section filter-section--ordenar">
        <span class="filter-section__label">Ordenar por</span>
        <div class="filter-chips">${ORDENACOES
          .map((o) => chipFiltro(o.rotulo, 'data-ordem', o.id, gridState.ordem === o.id))
          .join('')}</div>
      </div>`;

    corpo.innerHTML = html;

    const quantos = contarFiltros();
    const badge = document.getElementById('filter-badge');
    if (badge) {
      badge.textContent = quantos;
      badge.hidden = quantos === 0;
    }

    const limpar = document.getElementById('filter-clear');
    if (limpar) limpar.classList.toggle('is-active', quantos > 0 || gridState.ordem !== 'relevancia');
  }

  function aplicarFiltro(mudanca) {
    Object.assign(gridState, mudanca, { pagina: 1 });
    montarPainelFiltros();
    renderProductGrid();
  }

  function fecharPainelFiltros() {
    const dropdown = document.getElementById('filter-dropdown');
    if (dropdown) dropdown.classList.remove('is-open');
    const toggle = document.getElementById('filter-toggle');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  function renderProductGrid() {
    const productGrid = document.getElementById('product-grid');
    if (!productGrid || !gridState) return;

    const PRODUTOS = window.PRODUTOS || [];
    const catalogoVazio = PRODUTOS.length === 0;
    const q = gridState.busca.trim().toLowerCase();
    const familia = familiaAtual();
    // familia sem folha escolhida vale por todas as categorias dela
    const categorias = gridState.categoria ? [gridState.categoria] : (familia ? familia.categorias : null);
    const faixa = FAIXAS_PRECO.find((f) => f.id === gridState.faixa);

    const filtrados = PRODUTOS.filter((p) => {
      if (categorias && categorias.indexOf(p.categoria) === -1) return false;
      if (faixa && !(p.precoNumerico >= faixa.min && p.precoNumerico < faixa.max)) return false;
      if (gridState.tamanhos.length) {
        const tamanhos = p.tamanhos || [];
        if (!gridState.tamanhos.some((t) => tamanhos.indexOf(t) !== -1)) return false;
      }
      if (q && !p.nome.toLowerCase().includes(q) && !p.categoria.toLowerCase().includes(q)) return false;
      return true;
    });

    ordenar(filtrados);

    const totalPaginas = Math.max(1, Math.ceil(filtrados.length / PRODUTOS_POR_PAGINA));
    if (gridState.pagina > totalPaginas) gridState.pagina = totalPaginas;
    if (gridState.pagina < 1) gridState.pagina = 1;

    const inicio = (gridState.pagina - 1) * PRODUTOS_POR_PAGINA;
    const itensPagina = filtrados.slice(inicio, inicio + PRODUTOS_POR_PAGINA);
    const quantidadeVazios = catalogoVazio
      ? PRODUTOS_POR_PAGINA
      : Math.max(0, PRODUTOS_POR_PAGINA - itensPagina.length);

    productGrid.innerHTML =
      itensPagina.map(cartaoProduto).join('') + cartaoVazio().repeat(quantidadeVazios);

    const emptyState = document.getElementById('empty-state');
    const collectionTitle = document.getElementById('collection-title');
    if (emptyState) emptyState.classList.toggle('is-visible', filtrados.length === 0 && !catalogoVazio);
    if (collectionTitle) collectionTitle.textContent = tituloDaColecao();

    const collectionSummary = document.getElementById('collection-summary');
    if (collectionSummary) collectionSummary.textContent = resumoDosFiltros(filtrados.length);

    renderPaginacao(totalPaginas);
  }

  function registrarBusca(termo) {
    if (!window.BUSCAS_URL) return;
    fetch(window.BUSCAS_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        Accept: 'application/json',
      },
      body: JSON.stringify({ termo }),
    }).catch(() => {});
  }

  function alternarFavorito(e) {
    const botaoFavorito = e.target.closest('.btn-favorite');
    if (botaoFavorito) {
      if (!window.IS_AUTHENTICATED) {
        window.ajaxNavigate ? window.ajaxNavigate(window.LOGIN_URL) : (window.location.href = window.LOGIN_URL);
        return;
      }

      const productId = botaoFavorito.dataset.id;
      botaoFavorito.disabled = true;

      fetch(`${window.FAVORITES_TOGGLE_URL}/${productId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          Accept: 'application/json',
        },
      })
        .then((res) => res.json())
        .then((data) => {
          botaoFavorito.classList.toggle('is-active', data.favoritado);
          const svg = botaoFavorito.querySelector('svg');
          if (svg) svg.setAttribute('fill', data.favoritado ? 'currentColor' : 'none');
        })
        .catch(() => mostrarToast('Não foi possível atualizar os favoritos.', 'erro'))
        .finally(() => {
          botaoFavorito.disabled = false;
        });
    }
  }

  function initHomeGrid() {
    const productGrid = document.getElementById('product-grid');
    if (!productGrid) {
      gridState = null;
      return;
    }
    gridState = {
      familia: null,
      categoria: null,
      tamanhos: [],
      faixa: null,
      ordem: 'relevancia',
      busca: '',
      pagina: 1,
    };
    montarPainelFiltros();
    renderProductGrid();
    initCarrosseis();
  }

  /* Listeners globais delegados em document — configurados uma única vez,
     continuam funcionando mesmo quando o conteúdo é trocado via AJAX. */
  function setupGlobalListeners() {
    if (setupGlobalListeners.done) return;
    setupGlobalListeners.done = true;

    document.addEventListener('click', (e) => {
      const filterToggle = e.target.closest('#filter-toggle');
      const filterDropdown = document.getElementById('filter-dropdown');
      if (filterToggle && filterDropdown) {
        e.stopPropagation();
        const isOpen = filterDropdown.classList.toggle('is-open');
        filterToggle.setAttribute('aria-expanded', String(isOpen));
        return;
      }

      if (filterDropdown && filterDropdown.classList.contains('is-open') && !filterDropdown.contains(e.target)) {
        fecharPainelFiltros();
      }

      if (gridState) {
        const abaFamilia = e.target.closest('.filter-tab');
        if (abaFamilia) {
          // trocar de familia zera folha e tamanho: a grade de calcado (34-40)
          // nao vale para roupa (PP-GG)
          aplicarFiltro({
            familia: abaFamilia.dataset.familia || null,
            categoria: null,
            tamanhos: [],
          });
          return;
        }

        const chipCategoria = e.target.closest('[data-categoria]');
        if (chipCategoria) {
          const valor = chipCategoria.dataset.categoria;
          aplicarFiltro({ categoria: gridState.categoria === valor ? null : valor });
          return;
        }

        const chipTamanho = e.target.closest('[data-tamanho]');
        if (chipTamanho) {
          const valor = chipTamanho.dataset.tamanho;
          const escolhidos = gridState.tamanhos.slice();
          const posicao = escolhidos.indexOf(valor);
          if (posicao === -1) escolhidos.push(valor);
          else escolhidos.splice(posicao, 1);
          aplicarFiltro({ tamanhos: escolhidos });
          return;
        }

        const chipFaixa = e.target.closest('[data-faixa]');
        if (chipFaixa) {
          const valor = chipFaixa.dataset.faixa;
          aplicarFiltro({ faixa: gridState.faixa === valor ? null : valor });
          return;
        }

        const chipOrdem = e.target.closest('[data-ordem]');
        if (chipOrdem) {
          aplicarFiltro({ ordem: chipOrdem.dataset.ordem });
          return;
        }

        if (e.target.closest('#filter-clear')) {
          aplicarFiltro({
            familia: null,
            categoria: null,
            tamanhos: [],
            faixa: null,
            ordem: 'relevancia',
          });
          return;
        }

        if (e.target.closest('#filter-apply')) {
          fecharPainelFiltros();
          return;
        }
      }

      const carrosselPrev = e.target.closest('[data-carousel-prev]');
      if (carrosselPrev) {
        const track = document.getElementById('carousel-' + carrosselPrev.dataset.carouselPrev);
        if (track) track.scrollBy({ left: -track.clientWidth * 0.9, behavior: 'smooth' });
        return;
      }

      const carrosselNext = e.target.closest('[data-carousel-next]');
      if (carrosselNext) {
        const track = document.getElementById('carousel-' + carrosselNext.dataset.carouselNext);
        if (track) track.scrollBy({ left: track.clientWidth * 0.9, behavior: 'smooth' });
        return;
      }

      const paginaBtn = e.target.closest('.pagination__page');
      if (paginaBtn && gridState) {
        gridState.pagina = Number(paginaBtn.dataset.pagina);
        renderProductGrid();
        document.getElementById('colecao')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
      }

      const profileToggle = e.target.closest('#profile-toggle');
      const profileDropdown = document.getElementById('profile-dropdown');
      if (profileToggle && profileDropdown) {
        e.stopPropagation();
        const isOpen = profileDropdown.classList.toggle('is-open');
        profileToggle.setAttribute('aria-expanded', String(isOpen));
      } else if (profileDropdown && profileDropdown.classList.contains('is-open') && !profileDropdown.contains(e.target)) {
        profileDropdown.classList.remove('is-open');
        const t = document.getElementById('profile-toggle');
        if (t) t.setAttribute('aria-expanded', 'false');
      }

      const notificationToggle = e.target.closest('#notification-toggle');
      const notificationDropdown = document.getElementById('notification-dropdown');
      if (notificationToggle && notificationDropdown) {
        e.stopPropagation();
        const isOpen = notificationDropdown.classList.toggle('is-open');
        notificationToggle.setAttribute('aria-expanded', String(isOpen));
      } else if (notificationDropdown && notificationDropdown.classList.contains('is-open') && !notificationDropdown.contains(e.target)) {
        notificationDropdown.classList.remove('is-open');
        const t = document.getElementById('notification-toggle');
        if (t) t.setAttribute('aria-expanded', 'false');
      }

      const mobileMenuToggle = e.target.closest('#mobile-menu-toggle');
      const mobileMenu = document.getElementById('mobile-menu');
      if (mobileMenuToggle && mobileMenu) {
        e.stopPropagation();
        const isOpen = mobileMenu.classList.toggle('is-open');
        mobileMenuToggle.setAttribute('aria-expanded', String(isOpen));
        mobileMenu.setAttribute('aria-hidden', String(!isOpen));
        return;
      } else if (mobileMenu && mobileMenu.classList.contains('is-open') && !mobileMenu.querySelector('.mobile-menu__panel').contains(e.target)) {
        mobileMenu.classList.remove('is-open');
        mobileMenu.setAttribute('aria-hidden', 'true');
        const t = document.getElementById('mobile-menu-toggle');
        if (t) t.setAttribute('aria-expanded', 'false');
      }

      const searchIconBtn = e.target.closest('#search-icon');
      if (searchIconBtn) {
        const input = document.getElementById('search-input');
        if (input) input.focus();
        window.location.hash = '#colecao';
        return;
      }

      const productGrid = document.getElementById('product-grid');
      if (productGrid && productGrid.contains(e.target)) {
        alternarFavorito(e);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;
      fecharPainelFiltros();
      const pd = document.getElementById('profile-dropdown');
      if (pd) {
        pd.classList.remove('is-open');
        const t2 = document.getElementById('profile-toggle');
        if (t2) t2.setAttribute('aria-expanded', 'false');
      }
      const nd = document.getElementById('notification-dropdown');
      if (nd) {
        nd.classList.remove('is-open');
        const t3 = document.getElementById('notification-toggle');
        if (t3) t3.setAttribute('aria-expanded', 'false');
      }
      const mm = document.getElementById('mobile-menu');
      if (mm) {
        mm.classList.remove('is-open');
        mm.setAttribute('aria-hidden', 'true');
        const t4 = document.getElementById('mobile-menu-toggle');
        if (t4) t4.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('input', (e) => {
      if (e.target.id !== 'search-input' || !gridState) return;
      gridState.busca = e.target.value;
      gridState.pagina = 1;
      renderProductGrid();

      clearTimeout(buscaTimeout);
      const termo = e.target.value.trim();
      if (!termo) return;
      buscaTimeout = setTimeout(() => registrarBusca(termo), 700);
    });
  }

  setupGlobalListeners();

  if (window.registerPageInit) {
    window.registerPageInit(initHomeGrid);
  } else {
    initHomeGrid();
  }
})();
