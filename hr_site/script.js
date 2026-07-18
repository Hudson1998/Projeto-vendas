const PRODUTOS = [
  { nome: 'Vestido Midi Preto', preco: 'R$ 189,90', categoria: 'Vestidos', url: 'assets/p1.jpg' },
  { nome: 'Vestido Longo Cetim', preco: 'R$ 229,90', categoria: 'Vestidos', url: 'assets/p2.jpg' },
  { nome: 'Vestido Tubinho', preco: 'R$ 199,90', categoria: 'Vestidos', url: 'assets/p3.jpg' },
  { nome: 'Saia Plissada', preco: 'R$ 159,90', categoria: 'Saias', url: 'assets/p4.jpg' },
  { nome: 'Saia Lápis Cinza', preco: 'R$ 139,90', categoria: 'Saias', url: 'assets/p5.jpg' },
  { nome: 'Camisa de Seda Off-White', preco: 'R$ 169,90', categoria: 'Camisas', url: 'assets/p6.jpg' },
  { nome: 'Camisa Alfaiataria', preco: 'R$ 149,90', categoria: 'Camisas', url: 'assets/p7.jpg' },
  { nome: 'Calça Pantalona', preco: 'R$ 179,90', categoria: 'Calças', url: 'assets/p8.jpg' },
  { nome: 'Bolsa Estruturada', preco: 'R$ 199,90', categoria: 'Acessórios', url: 'assets/p9.jpg' },
  { nome: 'Cinto Fivela Dourada', preco: 'R$ 89,90', categoria: 'Acessórios', url: 'assets/p10.jpg' },
];

const state = {
  cat: 'Todos',
  busca: '',
};

const navLinks = document.querySelectorAll('.nav-link[data-cat]');
const searchInput = document.getElementById('search-input');
const searchIcon = document.getElementById('search-icon');
const productGrid = document.getElementById('product-grid');
const emptyState = document.getElementById('empty-state');
const collectionTitle = document.getElementById('collection-title');
const collectionCount = document.getElementById('collection-count');

function render() {
  const q = state.busca.trim().toLowerCase();
  const filtrados = PRODUTOS.filter((p) =>
    (state.cat === 'Todos' || p.categoria === state.cat) &&
    (!q || p.nome.toLowerCase().includes(q) || p.categoria.toLowerCase().includes(q))
  );

  navLinks.forEach((link) => {
    link.classList.toggle('is-active', link.dataset.cat === state.cat);
  });

  productGrid.innerHTML = filtrados
    .map(
      (item) => `
    <div class="product-card">
      <img class="product-card__image" src="${item.url}" alt="${item.nome}">
      <div class="product-card__body">
        <span class="product-card__category">${item.categoria}</span>
        <span class="product-card__name">${item.nome}</span>
        <span class="product-card__price">${item.preco}</span>
      </div>
    </div>`
    )
    .join('');

  emptyState.classList.toggle('is-visible', filtrados.length === 0);
  collectionTitle.textContent = state.cat === 'Todos' ? 'Coleção' : state.cat;
  collectionCount.textContent = filtrados.length + (filtrados.length === 1 ? ' peça' : ' peças');
}

navLinks.forEach((link) => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    state.cat = link.dataset.cat;
    render();
  });
});

searchInput.addEventListener('input', (e) => {
  state.busca = e.target.value;
  render();
});

searchIcon.addEventListener('click', () => {
  searchInput.focus();
  window.location.hash = '#colecao';
});

render();
