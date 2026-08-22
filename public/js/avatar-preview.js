/* Pre-visualiza a imagem escolhida no campo de foto/logotipo antes do envio.
   Serve as duas telas de perfil (cliente e lojista), por isso vive num arquivo
   proprio em vez de repetido inline em cada view.

   Convencao: o input de arquivo tem id "foto"; a <img> de preview tem id
   "avatar-preview"; o monograma que ela substitui, quando existe, tem id
   "avatar-preview-initials". */
(() => {
  const input = document.getElementById('foto');
  const preview = document.getElementById('avatar-preview');
  if (!input || !preview) return;

  const iniciais = document.getElementById('avatar-preview-initials');

  input.addEventListener('change', () => {
    const arquivo = input.files && input.files[0];
    if (!arquivo) return;

    // o objectURL nasce preso ao documento; soltar depois que a imagem carrega
    // evita segurar o arquivo inteiro na memoria enquanto a pagina viver
    const url = URL.createObjectURL(arquivo);
    preview.onload = () => URL.revokeObjectURL(url);
    preview.src = url;
    preview.hidden = false;

    if (iniciais) iniciais.hidden = true;

    // escolher uma foto nova e o oposto de remover a atual
    const remover = document.querySelector('input[name="remover_foto"]');
    if (remover) remover.checked = false;
  });
})();
