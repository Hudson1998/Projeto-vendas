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

  function swapContent(html, finalUrl) {
    var parser = new DOMParser();
    var doc = parser.parseFromString(html, 'text/html');
    var newContent = doc.getElementById(contentId);
    var current = document.getElementById(contentId);

    if (!newContent || !current) {
      window.location.href = finalUrl;
      return false;
    }

    if (document.querySelector('app-root, app-loja-root') && window.__ngAppRef) {
      window.__ngAppRef.destroy();
      window.__ngAppRef = undefined;
    }

    /* O #confirm-modal mora dentro do #ajax-content e some junto com a troca,
       mas a trava de rolagem que ele acende (body.modal-open -> overflow:
       hidden) fica no <body> e sobrevive. Navegar com o modal aberto -- que e
       exatamente o que "Pagar agora" faz -- deixava a pagina de destino sem
       barra de rolagem, presa, ate um F5. Solta a trava junto com o modal. */
    document.body.classList.remove('modal-open');

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

    fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
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

    var fetchOptions = {
      method: method,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
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
