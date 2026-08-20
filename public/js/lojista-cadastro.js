(function () {
  function onlyDigits(value) {
    return (value || '').replace(/\D/g, '');
  }

  function maskTelefone(value) {
    const d = onlyDigits(value).slice(0, 11);
    if (d.length <= 10) {
      return d.replace(/(\d{2})(\d{0,4})(\d{0,4})/, function (m, ddd, a, b) {
        return [ddd && '(' + ddd + ')', a, b].filter(Boolean).join(' ').replace(/^\((\d{2})\) $/, '($1) ');
      }).trim();
    }
    return d.replace(/(\d{2})(\d{5})(\d{0,4})/, function (m, ddd, a, b) {
      return '(' + ddd + ') ' + a + (b ? '-' + b : '');
    });
  }

  function maskCpf(value) {
    const d = onlyDigits(value).slice(0, 11);
    return d
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  }

  function maskCnpj(value) {
    const d = onlyDigits(value).slice(0, 14);
    return d
      .replace(/(\d{2})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1.$2')
      .replace(/(\d{3})(\d)/, '$1/$2')
      .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
  }

  function cpfValido(cpfComMascara) {
    const cpf = onlyDigits(cpfComMascara);
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    for (let t = 9; t < 11; t++) {
      let soma = 0;
      for (let c = 0; c < t; c++) soma += parseInt(cpf.charAt(c), 10) * ((t + 1) - c);
      let digito = ((10 * soma) % 11) % 10;
      if (parseInt(cpf.charAt(t), 10) !== digito) return false;
    }
    return true;
  }

  function cnpjValido(cnpjComMascara) {
    const cnpj = onlyDigits(cnpjComMascara);
    if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
    const calcularDigito = (base, pesos) => {
      let soma = 0;
      for (let i = 0; i < pesos.length; i++) soma += parseInt(base.charAt(i), 10) * pesos[i];
      const resto = soma % 11;
      return resto < 2 ? 0 : 11 - resto;
    };
    const d1 = calcularDigito(cnpj, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
    if (d1 !== parseInt(cnpj.charAt(12), 10)) return false;
    const d2 = calcularDigito(cnpj, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
    return d2 === parseInt(cnpj.charAt(13), 10);
  }

  function setHint(hintEl, valido) {
    if (!hintEl) return;
    if (!hintEl.previousElementSibling || !hintEl.previousElementSibling.value) {
      hintEl.textContent = '';
      hintEl.className = 'field__hint';
      return;
    }
    hintEl.textContent = valido ? 'Documento válido.' : 'Documento inválido, confira os números.';
    hintEl.className = 'field__hint ' + (valido ? 'field__hint--ok' : 'field__hint--error');
  }

  function atualizarTipoPessoa() {
    const radioSelecionado = document.querySelector('input[name="tipo_pessoa"]:checked');
    if (!radioSelecionado) return;

    const juridica = radioSelecionado.value === 'juridica';

    document.getElementById('bloco-cpf').style.display = juridica ? 'none' : 'block';
    document.getElementById('bloco-cnpj').style.display = juridica ? 'block' : 'none';
    document.getElementById('bloco-razao-social').style.display = juridica ? 'block' : 'none';
    document.getElementById('bloco-contrato-social').style.display = juridica ? 'block' : 'none';

    document.getElementById('cpf').required = !juridica;
    document.getElementById('cnpj').required = juridica;
    document.getElementById('razao_social').required = juridica;
    document.getElementById('contrato_social_mei').required = juridica;
  }

  function atualizarIsencaoIe() {
    const campoIsento = document.getElementById('ie_isento');
    if (!campoIsento) return;

    const isento = campoIsento.checked;
    const campoIe = document.getElementById('inscricao_estadual');
    campoIe.disabled = isento;
    campoIe.required = !isento;
    document.getElementById('bloco-ie').style.opacity = isento ? '0.5' : '1';
    if (isento) campoIe.value = '';
  }

  function initLojistaCadastro() {
    const radios = document.querySelectorAll('[data-toggle-tipo-pessoa]');
    if (!radios.length) return;

    radios.forEach((radio) => {
      radio.addEventListener('change', atualizarTipoPessoa);
    });
    document.getElementById('ie_isento')?.addEventListener('change', atualizarIsencaoIe);

    atualizarTipoPessoa();
    atualizarIsencaoIe();
  }

  if (!window.__lojistaCadastroBound) {
    window.__lojistaCadastroBound = true;

    document.addEventListener('input', function (e) {
      if (e.target.id === 'telefone') {
        e.target.value = maskTelefone(e.target.value);
        return;
      }
      if (e.target.id === 'cpf') {
        e.target.value = maskCpf(e.target.value);
        setHint(document.getElementById('cpf-hint'), cpfValido(e.target.value));
        return;
      }
      if (e.target.id === 'cnpj') {
        e.target.value = maskCnpj(e.target.value);
        setHint(document.getElementById('cnpj-hint'), cnpjValido(e.target.value));
        return;
      }
    });
  }

  if (window.registerPageInit) {
    window.registerPageInit(initLojistaCadastro);
  } else {
    initLojistaCadastro();
  }
})();
