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

  function renderProductGrid() {
    const productGrid = document.getElementById('product-grid');
    if (!productGrid || !gridState) return;

    const PRODUTOS = window.PRODUTOS || [];
    const q = gridState.busca.trim().toLowerCase();
    const filtrados = PRODUTOS.filter(
      (p) =>
        (gridState.cat === 'Todos' || p.categoria === gridState.cat) &&
        (!q || p.nome.toLowerCase().includes(q) || p.categoria.toLowerCase().includes(q))
    );

    productGrid.innerHTML = filtrados
      .map(
        (item) => `
      <div class="product-card">
        <a href="/produtos/${item.id}" class="product-card__link">
          <div class="product-card__image-wrap">
            <img class="product-card__image" src="${item.url}" alt="${item.nome}">
          </div>
          <div class="product-card__body">
            <span class="product-card__category">${item.categoria}</span>
            <span class="product-card__name">${item.nome}</span>
            <span class="product-card__price">${item.preco}</span>
          </div>
        </a>
        <button type="button" class="btn-favorite ${item.favoritado ? 'is-active' : ''}" data-id="${item.id}" title="Favoritar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="${item.favoritado ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="1.6"><path d="M12 21s-7.5-4.6-10-9.1C.4 8.3 2 4.5 5.6 4c2-.3 3.9.6 6.4 3 2.5-2.4 4.4-3.3 6.4-3 3.6.5 5.2 4.3 3.6 7.9-2.5 4.5-10 9.1-10 9.1z"/></svg>
        </button>
      </div>`
      )
      .join('');

    const emptyState = document.getElementById('empty-state');
    const collectionTitle = document.getElementById('collection-title');
    const collectionCount = document.getElementById('collection-count');
    if (emptyState) emptyState.classList.toggle('is-visible', filtrados.length === 0);
    if (collectionTitle) collectionTitle.textContent = gridState.cat === 'Todos' ? 'Coleção' : gridState.cat;
    if (collectionCount) {
      collectionCount.textContent = filtrados.length + (filtrados.length === 1 ? ' peça' : ' peças');
    }
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
    gridState = { cat: 'Todos', busca: '' };
    renderProductGrid();
  }

  /* Listeners globais delegados em document — configurados uma única vez,
     continuam funcionando mesmo quando o conteúdo é trocado via AJAX. */
  function setupGlobalListeners() {
    if (setupGlobalListeners.done) return;
    setupGlobalListeners.done = true;

    document.addEventListener('click', (e) => {
      const categoryToggle = e.target.closest('#category-toggle');
      const categoryDropdown = document.getElementById('category-dropdown');
      if (categoryToggle && categoryDropdown) {
        e.stopPropagation();
        const isOpen = categoryDropdown.classList.toggle('is-open');
        categoryToggle.setAttribute('aria-expanded', String(isOpen));
        return;
      }

      const categoryOption = e.target.closest('.category-option');
      if (categoryOption && gridState) {
        gridState.cat = categoryOption.dataset.cat;
        const label = document.getElementById('category-label');
        if (label) label.textContent = categoryOption.dataset.cat;
        document.querySelectorAll('.category-option').forEach((o) => o.classList.toggle('is-active', o === categoryOption));
        const dd = document.getElementById('category-dropdown');
        const dt = document.getElementById('category-toggle');
        if (dd) dd.classList.remove('is-open');
        if (dt) dt.setAttribute('aria-expanded', 'false');
        renderProductGrid();
        return;
      }

      const ddOpen = document.getElementById('category-dropdown');
      if (ddOpen && ddOpen.classList.contains('is-open') && !ddOpen.contains(e.target)) {
        ddOpen.classList.remove('is-open');
        const t = document.getElementById('category-toggle');
        if (t) t.setAttribute('aria-expanded', 'false');
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
      const dd = document.getElementById('category-dropdown');
      if (dd) {
        dd.classList.remove('is-open');
        const t = document.getElementById('category-toggle');
        if (t) t.setAttribute('aria-expanded', 'false');
      }
      const pd = document.getElementById('profile-dropdown');
      if (pd) {
        pd.classList.remove('is-open');
        const t2 = document.getElementById('profile-toggle');
        if (t2) t2.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('input', (e) => {
      if (e.target.id !== 'search-input' || !gridState) return;
      gridState.busca = e.target.value;
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
