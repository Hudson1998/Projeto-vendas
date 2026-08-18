(function () {
  function onlyDigits(value) {
    return (value || '').replace(/\D/g, '');
  }

  function maskCep(value) {
    const digits = onlyDigits(value).slice(0, 8);
    return digits.length > 5 ? digits.slice(0, 5) + '-' + digits.slice(5) : digits;
  }

  function montarEndereco() {
    const endereco = document.getElementById('endereco');
    const rua = document.getElementById('rua');
    const numero = document.getElementById('numero');
    const complemento = document.getElementById('complemento');
    const bairro = document.getElementById('bairro');
    const cidade = document.getElementById('cidade');
    const uf = document.getElementById('uf');
    const cep = document.getElementById('cep');
    if (!endereco || !rua || !numero || !bairro || !cidade || !uf || !cep) return;

    const partes = [];
    let linha1 = rua.value.trim();
    if (numero.value.trim()) linha1 += ', ' + numero.value.trim();
    if (complemento && complemento.value.trim()) linha1 += ' - ' + complemento.value.trim();
    if (linha1) partes.push(linha1);
    if (bairro.value.trim()) partes.push(bairro.value.trim());

    let linha2 = cidade.value.trim();
    if (uf.value.trim()) linha2 += (linha2 ? ' - ' : '') + uf.value.trim().toUpperCase();
    if (linha2) partes.push(linha2);

    if (cep.value.trim()) partes.push('CEP ' + cep.value.trim());

    if (partes.length) {
      endereco.value = partes.join(', ');
    }
  }

  function buscarCep(cepDigits) {
    const hint = document.getElementById('cep-hint');
    const rua = document.getElementById('rua');
    const bairro = document.getElementById('bairro');
    const cidade = document.getElementById('cidade');
    const uf = document.getElementById('uf');
    const numero = document.getElementById('numero');

    if (hint) {
      hint.textContent = 'Buscando endereço...';
      hint.className = 'field__hint';
    }

    fetch('https://viacep.com.br/ws/' + cepDigits + '/json/')
      .then((res) => res.json())
      .then((data) => {
        if (data.erro) {
          if (hint) {
            hint.textContent = 'CEP não encontrado. Preencha o endereço manualmente.';
            hint.className = 'field__hint field__hint--error';
          }
          return;
        }
        if (rua) rua.value = data.logradouro || rua.value;
        if (bairro) bairro.value = data.bairro || bairro.value;
        if (cidade) cidade.value = data.localidade || '';
        if (uf) uf.value = data.uf || '';
        if (hint) {
          hint.textContent = 'Endereço encontrado! Confira e complete o número.';
          hint.className = 'field__hint field__hint--ok';
        }
        if (numero) numero.focus();
        montarEndereco();
      })
      .catch(() => {
        if (hint) {
          hint.textContent = 'Não foi possível buscar o CEP agora. Preencha o endereço manualmente.';
          hint.className = 'field__hint field__hint--error';
        }
      });
  }

  document.addEventListener('input', function (e) {
    if (e.target.id === 'cep') {
      e.target.value = maskCep(e.target.value);
      const digits = onlyDigits(e.target.value);
      if (digits.length === 8) {
        buscarCep(digits);
      }
      montarEndereco();
      return;
    }

    if (['rua', 'numero', 'complemento', 'bairro', 'cidade', 'uf'].indexOf(e.target.id) !== -1) {
      montarEndereco();
    }
  });
})();
