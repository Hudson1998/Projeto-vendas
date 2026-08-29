(function () {
  var contentId = 'ajax-content';
  var loaderEl = null;
  var loaderTimer = null;

  /* Dois registros, e nao um so.
     Os JS do layout (app.js, flash.js, cep.js...) carregam uma unica vez e
     valem para sempre. Ja o <script> inline de uma pagina vive dentro do
     #ajax-content (ou do #ajax-scripts) e e re-executado a cada troca.
     Guardar os dois no mesmo array fazia a lista crescer sem parar: cada
     visita ao /carrinho deixava mais uma copia do inicializador dele, que
     continuava rodando -- e religando listeners -- em todas as paginas
     seguintes. Dois modais abrindo sobre o mesmo clique era o sintoma. */
  var globalInits = [];
  var pageInits = [];
  var trocandoPagina = false;

  /* De onde veio a chamada: script do layout ou script da pagina.
     E o elemento <script> que decide, nao o momento da chamada -- na primeira
     carga (sem troca nenhuma) o script da pagina tambem precisa entrar como
     "de pagina", senao ficaria no registro permanente e vazaria do mesmo
     jeito quando o usuario saisse e voltasse. */
  function ehScriptDePagina() {
    var script = document.currentScript;
    if (!script || !script.closest) return trocandoPagina;
    return !!script.closest('#' + contentId + ', #ajax-scripts');
  }

  window.registerPageInit = function registerPageInit(fn) {
    if (ehScriptDePagina()) {
      pageInits.push(fn);
      // durante uma troca quem executa e o afterContentReady(); chamar aqui
      // tambem rodaria o inicializador duas vezes e dobraria os listeners
      if (!trocandoPagina) fn();
      return;
    }

    globalInits.push(fn);
    fn();
  };

  function getLoader() {
    if (!loaderEl) loaderEl = document.getElementById('page-loader');
    return loaderEl;
  }

  function showLoader() {
    clearTimeout(loaderTimer);
    // pequeno atraso evita "piscar" o loader em navegações muito rápidas
    loaderTimer = setTimeout(function () {
      getLoader() && getLoader().classList.add('is-visible');
    }, 120);
  }

  function hideLoader() {
    clearTimeout(loaderTimer);
    getLoader() && getLoader().classList.remove('is-visible');
  }

  function isAjaxable(url) {
    try {
      var target = new URL(url, window.location.href);
      if (target.origin !== window.location.origin) return false;
      if (target.pathname === window.location.pathname && target.search === window.location.search && target.hash) {
        return false; // âncora na mesma página
      }
      return true;
    } catch (e) {
      return false;
    }
  }

  function runScripts(container) {
    var scripts = container.querySelectorAll('script');
    scripts.forEach(function (oldScript) {
      var newScript = document.createElement('script');
      Array.prototype.forEach.call(oldScript.attributes, function (attr) {
        newScript.setAttribute(attr.name, attr.value);
      });
      newScript.textContent = oldScript.textContent;
      oldScript.parentNode.replaceChild(newScript, oldScript);
    });
  }

  function afterContentReady() {
    globalInits.concat(pageInits).forEach(function (fn) {
      try {
        fn();
      } catch (e) {
        console.error(e);
      }
    });

    // as duas raizes Angular: <app-root> no admin, <app-loja-root> na loja
    if (document.querySelector('app-root, app-loja-root') && typeof window.__bootstrapAdminCharts === 'function') {
      window.__bootstrapAdminCharts();
    }

    document.dispatchEvent(new CustomEvent('ajaxpage:loaded'));
  }

  /**
   * O que identifica o layout de uma pagina: os <script> do rodape.
   *
   * Conta so o que esta FORA do #ajax-content e do #ajax-scripts -- esses dois
   * mudam de pagina para pagina DENTRO do mesmo layout (o bundle do Angular no
   * dashboard, o avatar-preview no perfil) e fariam toda navegacao parecer uma
   * troca de layout.
   */
  function assinaturaDoLayout(documento) {
    var srcs = [];

    Array.prototype.forEach.call(documento.querySelectorAll('script[src]'), function (script) {
      if (script.closest('#' + contentId + ', #ajax-scripts')) return;
      srcs.push((script.getAttribute('src') || '').split('?')[0]);
    });

    return srcs.sort().join('|');
  }

  function swapContent(html, finalUrl) {
    var parser = new DOMParser();
    var doc = parser.parseFromString(html, 'text/html');
    var newContent = doc.getElementById(contentId);
    var current = document.getElementById(contentId);

    if (!newContent || !current) {
      window.location.href = finalUrl;
      return false;
    }

    /* Troca de layout vai pelo navegador, nao pelo AJAX.
       A troca substitui o #ajax-content e mais nada -- mas quando o destino
       usa outro layout (vitrine -> /loja, painel -> vitrine) o que fica de
       fora e justamente o que muda: os <script> do rodape. O conteudo do
       painel entrava e abria sem admin-nav.js nem loja-dashboard.js, ainda
       carregando o app.js da vitrine. Carga normal resolve de uma vez. */
    if (assinaturaDoLayout(doc) !== assinaturaDoLayout(document)) {
      window.location.href = finalUrl;
      return false;
    }

    if (document.querySelector('app-root, app-loja-root') && window.__ngAppRef) {
      window.__ngAppRef.destroy();
      window.__ngAppRef = undefined;
    }

    /* A classe do <body> vem do layout, fora do #ajax-content: copiada da
       pagina que chegou, e nao herdada da anterior. Os tokens do painel do
       lojista moram em .loja-body -- sem a classe, selo, faixa e trilho abrem
       sem cor.

       Copiar tambem solta a trava de rolagem do modal (body.modal-open): o
       #confirm-modal some com a troca, mas a classe ficava no <body> e a
       pagina de destino abria presa, sem barra de rolagem, ate um F5. */
    document.body.className = doc.body ? doc.body.className : '';

    // os inicializadores da pagina que esta saindo morrem com ela; os scripts
    // da pagina nova registram os seus logo abaixo, dentro do runScripts
    pageInits = [];
    trocandoPagina = true;

    try {
      trocarConteudo(doc, current, newContent);
    } finally {
      // se um script da pagina estourar, a flag nao pode ficar presa em true:
      // a proxima navegacao registraria tudo como "de pagina" e nada rodaria
      trocandoPagina = false;
    }

    afterContentReady();
    return true;
  }

  function trocarConteudo(doc, current, newContent) {
    document.title = doc.title || document.title;
    current.innerHTML = newContent.innerHTML;
    runScripts(current);

    // os scripts de @push('scripts') vivem fora do #ajax-content (precisam
    // carregar depois dos JS globais). Sem trocar este bloco tambem, a pagina
    // nova herda o script da anterior e o dela nunca executa -- foi o que
    // travava o modal do /cadastro ate dar F5.
    var newScripts = doc.getElementById('ajax-scripts');
    var currentScripts = document.getElementById('ajax-scripts');

    // o bloco so existe no layout da pagina que esta na tela agora. Saindo de
    // um layout sem ele (app/admin/loja) nao havia onde injetar, e o script da
    // pagina nova era descartado em silencio -- era isso que deixava os botoes
    // do /cadastro mortos ate dar F5. Cria o container sob demanda.
    if (newScripts && !currentScripts) {
      currentScripts = document.createElement('div');
      currentScripts.id = 'ajax-scripts';
      document.body.appendChild(currentScripts);
    }

    // sem bloco novo, esvazia: senao o script da pagina anterior fica no DOM
    if (currentScripts) {
      currentScripts.innerHTML = newScripts ? newScripts.innerHTML : '';
      runScripts(currentScripts);
    }
  }

  function visit(url, options) {
    options = options || {};
    var push = options.push !== false;

    showLoader();

      /* Accept: text/html nao e enfeite.
         Com o X-Requested-With sozinho o Laravel entende que quem pede aceita
         qualquer coisa e responde 422 JSON quando a validacao falha. O
         swapContent nao acha #ajax-content nesse JSON, cai na recarga total, e
         a pagina volta limpa: o erro some (nunca foi para a sessao) e o
         formulario reabre do zero. Pedindo HTML, o Laravel faz o redirect de
         volta com os erros e a troca de conteudo os mostra no lugar certo. */
    fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
      credentials: 'same-origin',
    })
      .then(function (res) {
        return res.text().then(function (html) {
          return { res: res, html: html };
        });
      })
      .then(function (result) {
        var ok = swapContent(result.html, result.res.url || url);
        if (ok && push) {
          history.pushState({ ajax: true }, '', result.res.url || url);
        }
        // 'instant' in window nunca era verdadeiro (window nao tem essa propriedade),
        // entao caia em 'auto', que obedece o scroll-behavior herdado. A forma de
        // dois argumentos sobe na hora, sem animacao para brigar com o usuario.
        window.scrollTo(0, 0);
      })
      .catch(function () {
        window.location.href = url;
      })
      .finally(function () {
        hideLoader();
      });
  }

  window.ajaxNavigate = visit;

  document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest('a[href]');
    if (!link) return;
    if (link.hasAttribute('data-no-ajax')) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;
    if (e.defaultPrevented || e.button !== 0) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

    var href = link.getAttribute('href') || '';
    if (!href || href.charAt(0) === '#') return;
    if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0 || href.indexOf('javascript:') === 0) return;
    if (!isAjaxable(href)) return;

    e.preventDefault();
    visit(link.href);
  });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.hasAttribute('data-no-ajax')) return;
    if (!isAjaxable(form.action)) return;

    e.preventDefault();

    var method = (form.getAttribute('method') || 'GET').toUpperCase();
    var formData = new FormData(form);
    var url = form.action;

    /* Accept: text/html nao e enfeite.
       Com o X-Requested-With sozinho o Laravel entende que quem pede aceita
       qualquer coisa e responde 422 JSON quando a validacao falha. O
       swapContent nao acha #ajax-content nesse JSON, cai na recarga total, e
       a pagina volta limpa: o erro some (nunca foi para a sessao) e o
       formulario reabre do zero. Pedindo HTML, o Laravel faz o redirect de
       volta com os erros e a troca de conteudo os mostra no lugar certo. */
    var fetchOptions = {
      method: method,
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
      credentials: 'same-origin',
    };

    if (method === 'GET') {
      var params = new URLSearchParams(formData);
      url += (url.indexOf('?') === -1 ? '?' : '&') + params.toString();
    } else {
      fetchOptions.body = formData;
    }

    showLoader();

    fetch(url, fetchOptions)
      .then(function (res) {
        return res.text().then(function (html) {
          return { res: res, html: html };
        });
      })
      .then(function (result) {
        var ok = swapContent(result.html, result.res.url || url);
        if (ok) {
          history.pushState({ ajax: true }, '', result.res.url || url);
        }
        // 'instant' in window nunca era verdadeiro (window nao tem essa propriedade),
        // entao caia em 'auto', que obedece o scroll-behavior herdado. A forma de
        // dois argumentos sobe na hora, sem animacao para brigar com o usuario.
        window.scrollTo(0, 0);
      })
      .catch(function () {
        form.submit();
      })
      .finally(function () {
        hideLoader();
      });
  });

  window.addEventListener('popstate', function () {
    visit(window.location.href, { push: false });
  });
})();
