@php
  $subclassesPorClasse = \App\Models\ProductSubclass::with('productClass')
      ->get()
      ->sortBy('nome')
      ->groupBy(fn ($subclasse) => $subclasse->productClass->nome);

  $variantesIniciais = [];
  if (old('variantes_tamanho')) {
      foreach (old('variantes_tamanho') as $i => $tamanho) {
          $variantesIniciais[] = [
              'tamanho' => $tamanho,
              'cor' => old('variantes_cor')[$i] ?? '',
              'cor_hex' => old('variantes_cor_hex')[$i] ?? '#000000',
              'estoque' => old('variantes_estoque')[$i] ?? 0,
          ];
      }
  } elseif ($product && $product->variants->isNotEmpty()) {
      foreach ($product->variants as $variante) {
          $variantesIniciais[] = [
              'tamanho' => $variante->tamanho,
              'cor' => $variante->cor,
              'cor_hex' => $variante->cor_hex ?? '#000000',
              'estoque' => $variante->estoque,
          ];
      }
  }
  if (empty($variantesIniciais)) {
      $variantesIniciais = [['tamanho' => '', 'cor' => '', 'cor_hex' => '#000000', 'estoque' => 0]];
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
  <label for="product_subclass_id">Classe / Subclasse</label>
  <select id="product_subclass_id" name="product_subclass_id">
    <option value="">Sem classificação</option>
    @foreach ($subclassesPorClasse as $classeNome => $subclasses)
      <optgroup label="{{ $classeNome }}">
        @foreach ($subclasses as $subclasse)
          <option value="{{ $subclasse->id }}" @selected((int) old('product_subclass_id', $product->product_subclass_id ?? '') === $subclasse->id)>{{ $subclasse->nome }}</option>
        @endforeach
      </optgroup>
    @endforeach
  </select>
</div>

<div class="field">
  <label for="preco">Preço de venda (R$)</label>
  <input type="number" id="preco" name="preco" value="{{ old('preco', $product->preco ?? '') }}" step="0.01" min="0" placeholder="0,00" required>
</div>

<div class="field">
  <label for="descricao">Descrição da peça</label>
  <textarea id="descricao" name="descricao" placeholder="Detalhes de tecido, caimento, cuidados...">{{ old('descricao', $product->descricao ?? '') }}</textarea>
</div>

<div class="field">
  <label>Variantes (tamanho / cor / estoque)</label>
  <p class="product-form-card__hint">Cadastre uma linha para cada combinação vendida. Deixe tamanho e/ou cor em branco se a peça não variar nesse aspecto.</p>
  <div id="variantes-repeater" class="variant-repeater">
    @foreach ($variantesIniciais as $variante)
      <div class="variant-repeater__row">
        <input type="text" name="variantes_tamanho[]" value="{{ $variante['tamanho'] }}" placeholder="Tamanho (ex: P)">
        <input type="text" name="variantes_cor[]" value="{{ $variante['cor'] }}" placeholder="Cor (ex: Preto)">
        <input type="color" name="variantes_cor_hex[]" value="{{ $variante['cor_hex'] }}">
        <input type="number" name="variantes_estoque[]" value="{{ $variante['estoque'] }}" step="1" min="0" placeholder="Estoque">
        <button type="button" class="link-btn link-btn--danger variant-repeater__remove">Remover</button>
      </div>
    @endforeach
  </div>
  <button type="button" id="variantes-add" class="link-btn">+ Adicionar variante</button>
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
  var repeater = document.getElementById('variantes-repeater');
  var addBtn = document.getElementById('variantes-add');

  function criarLinha() {
    var row = document.createElement('div');
    row.className = 'variant-repeater__row';
    row.innerHTML = '<input type="text" name="variantes_tamanho[]" placeholder="Tamanho (ex: P)">'
      + '<input type="text" name="variantes_cor[]" placeholder="Cor (ex: Preto)">'
      + '<input type="color" name="variantes_cor_hex[]" value="#000000">'
      + '<input type="number" name="variantes_estoque[]" step="1" min="0" placeholder="Estoque">'
      + '<button type="button" class="link-btn link-btn--danger variant-repeater__remove">Remover</button>';
    return row;
  }

  addBtn.addEventListener('click', function () {
    repeater.appendChild(criarLinha());
  });

  repeater.addEventListener('click', function (e) {
    var botao = e.target.closest('.variant-repeater__remove');
    if (!botao) return;
    var linha = botao.closest('.variant-repeater__row');
    if (repeater.children.length > 1) {
      linha.remove();
    } else {
      linha.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (input) { input.value = ''; });
    }
  });
})();
</script>
