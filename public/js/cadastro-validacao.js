/*
 * Validacao do cadastro no navegador.
 *
 * O `required` nativo do HTML nao serve aqui por dois motivos: a bolha do
 * navegador some sozinha e mostra um campo de cada vez, e os campos de
 * endereco nao tem `name` -- eles sao juntados pelo cep.js no hidden
 * #endereco, entao o servidor so reclama de "endereco", nunca de "rua" ou
 * "bairro". Quem aponta o campo exato tem de ser esta checagem.
 *
 * O servidor continua validando tudo de novo: isto e conveniencia, nao
 * seguranca (o formulario leva novalidate justamente para esta checagem
 * assumir o lugar da bolha nativa).
 */
(function () {
  var SENHA_MINIMA = 10;
  // mesma familia de simbolos que a regra symbols() do Laravel aceita
  var SIMBOLO = /[^\p{L}\p{N}]/u;

  function campoDe(input) {
    return input.closest('.field');
  }

  function marcar(input, mensagem) {
    input.classList.add('is-invalid');
    input.setAttribute('aria-invalid', 'true');

    var campo = campoDe(input);
    var erro = campo && campo.querySelector('.field__erro');

    if (erro) {
      erro.textContent = mensagem;
      erro.hidden = false;
    }
  }

  function limpar(input) {
    input.classList.remove('is-invalid');
    input.removeAttribute('aria-invalid');

    var campo = campoDe(input);
    var erro = campo && campo.querySelector('.field__erro');

    // so esconde o que este script escreveu: a mensagem que veio do servidor
    // fica ate a pessoa mexer no campo
    if (erro && !erro.dataset.doServidor) {
      erro.hidden = true;
      erro.textContent = '';
    }
  }

  function initCadastro() {
    var form = document.getElementById('form-cadastro');
    if (!form) return;

    var senha = document.getElementById('password');
    var confirmacao = document.getElementById('password_confirmation');
    var forca = document.getElementById('senha-forca');
    var erroConfirmacao = document.getElementById('erro-confirmacao');

    // as mensagens que vieram do servidor ficam marcadas para nao serem
    // apagadas pela limpeza acima
    form.querySelectorAll('.field__erro').forEach(function (erro) {
      if (!erro.hidden && erro.textContent.trim()) erro.dataset.doServidor = '1';
    });

    // ---- forca da senha, enquanto digita
    function avaliarSenha() {
      var valor = senha.value;
      var regras = {
        tamanho: valor.length >= SENHA_MINIMA,
        simbolo: SIMBOLO.test(valor),
      };

      forca.hidden = valor.length === 0;

      forca.querySelectorAll('.senha-forca__item').forEach(function (item) {
        item.classList.toggle('is-ok', regras[item.dataset.regra] === true);
      });

      return regras.tamanho && regras.simbolo;
    }

    senha.addEventListener('input', function () {
      avaliarSenha();
      limpar(senha);
    });

    confirmacao.addEventListener('input', function () {
      limpar(confirmacao);
      erroConfirmacao.hidden = true;
    });

    // mexeu no campo, a marcacao sai
    form.querySelectorAll('input').forEach(function (input) {
      input.addEventListener('input', function () {
        if (input.classList.contains('is-invalid')) limpar(input);
      });
    });

    // ---- na hora de enviar
    form.addEventListener('submit', function (e) {
      var falhas = [];

      form.querySelectorAll('input[data-obrigatorio]').forEach(function (input) {
        limpar(input);

        if (!input.value.trim()) {
          marcar(input, input.dataset.obrigatorio);
          falhas.push(input);
        }
      });

      if (senha.value && !avaliarSenha()) {
        var falta = senha.value.length < SENHA_MINIMA
          ? 'A senha precisa ter no mínimo ' + SENHA_MINIMA + ' caracteres.'
          : 'A senha precisa ter pelo menos um caractere especial.';
        marcar(senha, falta);
        falhas.push(senha);
      }

      if (senha.value && confirmacao.value && senha.value !== confirmacao.value) {
        marcar(confirmacao, 'As duas senhas não são iguais.');
        falhas.push(confirmacao);
      }

      if (!falhas.length) return;

      e.preventDefault();

      var resumo = document.getElementById('resumo-erros');
      if (resumo) {
        resumo.hidden = false;
        resumo.querySelector('[data-contagem]').textContent = falhas.length === 1
          ? 'Falta preencher 1 campo obrigatório.'
          : 'Faltam preencher ' + falhas.length + ' campos obrigatórios.';
      }

      // leva a pessoa ao primeiro problema em vez de deixar o erro fora da tela
      falhas[0].focus();
      falhas[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
    });
  }

  if (window.registerPageInit) {
    window.registerPageInit(initCadastro);
  } else {
    initCadastro();
  }
})();
