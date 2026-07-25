@php
  $coresIniciais = [];
  if (old('cores_nome')) {
      foreach (old('cores_nome') as $i => $nome) {
          $coresIniciais[] = ['nome' => $nome, 'hex' => old('cores_hex')[$i] ?? '#000000'];
      }
  } elseif ($product && $product->cores) {
      $coresIniciais = $product->cores;
  }
  if (empty($coresIniciais)) {
      $coresIniciais = [['nome' => '', 'hex' => '#000000']];
  }
@endphp

<div class="field">
  <label for="nome">Nome da peça</label>
  <input type="text" id="nome" name="nome" value="{{ old('nome', $product->nome ?? '') }}" required autofocus>
</div>

<div class="field">
  <label for="categoria">Tipo de roupa</label>
  <select id="categoria" name="categoria" required>
    <option value="" disabled {{ old('categoria', $product->categoria ?? '') ? '' : 'selected' }}>Selecione uma categoria</option>
    @foreach (['Camisas', 'Calças', 'Vestidos', 'Saias', 'Acessórios', 'Chapéus', 'Perfumes', 'Calçados'] as $opcao)
      <option value="{{ $opcao }}" @selected(old('categoria', $product->categoria ?? '') === $opcao)>{{ $opcao }}</option>
    @endforeach
  </select>
</div>

<div class="field">
  <label for="preco">Preço de venda (R$)</label>
  <input type="number" id="preco" name="preco" value="{{ old('preco', $product->preco ?? '') }}" step="0.01" min="0" placeholder="0,00" required>
</div>

<div class="field">
  <label for="custo">Custo de compra (R$)</label>
  <input type="number" id="custo" name="custo" value="{{ old('custo', $product->custo ?? '') }}" step="0.01" min="0" placeholder="0,00">
</div>

<div class="field">
  <label for="estoque">Estoque (unidades)</label>
  <input type="number" id="estoque" name="estoque" value="{{ old('estoque', $product->estoque ?? 0) }}" step="1" min="0" required>
</div>

<div class="field">
  <label for="descricao">Descrição da peça</label>
  <textarea id="descricao" name="descricao" placeholder="Detalhes de tecido, caimento, cuidados...">{{ old('descricao', $product->descricao ?? '') }}</textarea>
</div>

<div class="field">
  <label for="tamanhos">Tamanhos disponíveis</label>
  <input type="text" id="tamanhos" name="tamanhos" value="{{ old('tamanhos', $product && $product->tamanhos ? implode(', ', $product->tamanhos) : '') }}" placeholder="P, M, G, GG">
</div>

<div class="field">
  <label>Cores disponíveis</label>
  <div id="cores-repeater" class="color-repeater">
    @foreach ($coresIniciais as $cor)
      <div class="color-repeater__row">
        <input type="text" name="cores_nome[]" value="{{ $cor['nome'] }}" placeholder="Nome da cor (ex: Preto)">
        <input type="color" name="cores_hex[]" value="{{ $cor['hex'] }}">
        <button type="button" class="link-btn link-btn--danger color-repeater__remove">Remover</button>
      </div>
    @endforeach
  </div>
  <button type="button" id="cores-add" class="link-btn">+ Adicionar cor</button>
</div>

<div class="field">
  <label for="foto">Foto da peça</label>
  <input type="file" id="foto" name="foto" accept="image/png,image/jpeg,image/webp" {{ $product ? '' : 'required' }}>
  @if ($product)
    <p class="product-form-card__hint">Deixe em branco para manter a foto atual.</p>
  @endif
</div>

<script>
(function () {
  var repeater = document.getElementById('cores-repeater');
  var addBtn = document.getElementById('cores-add');

  function criarLinha() {
    var row = document.createElement('div');
    row.className = 'color-repeater__row';
    row.innerHTML = '<input type="text" name="cores_nome[]" placeholder="Nome da cor (ex: Preto)">'
      + '<input type="color" name="cores_hex[]" value="#000000">'
      + '<button type="button" class="link-btn link-btn--danger color-repeater__remove">Remover</button>';
    return row;
  }

  addBtn.addEventListener('click', function () {
    repeater.appendChild(criarLinha());
  });

  repeater.addEventListener('click', function (e) {
    var botao = e.target.closest('.color-repeater__remove');
    if (!botao) return;
    var linha = botao.closest('.color-repeater__row');
    if (repeater.children.length > 1) {
      linha.remove();
    } else {
      linha.querySelectorAll('input[type="text"]').forEach(function (input) { input.value = ''; });
    }
  });
})();
</script>
