(function () {
  var contentId = 'ajax-content';
  var loaderEl = null;
  var loaderTimer = null;
  window.__pageInitializers = window.__pageInitializers || [];

  window.registerPageInit = function registerPageInit(fn) {
    window.__pageInitializers.push(fn);
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
    window.__pageInitializers.forEach(function (fn) {
      try {
        fn();
      } catch (e) {
        console.error(e);
      }
    });

    if (document.querySelector('app-root') && typeof window.__bootstrapAdminCharts === 'function') {
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

    if (document.querySelector('app-root') && window.__ngAppRef) {
      window.__ngAppRef.destroy();
      window.__ngAppRef = undefined;
    }

    document.title = doc.title || document.title;
    current.innerHTML = newContent.innerHTML;
    runScripts(current);

    // os scripts de @push('scripts') vivem fora do #ajax-content (precisam
    // carregar depois dos JS globais). Sem trocar este bloco tambem, a pagina
    // nova herda o script da anterior e o dela nunca executa -- foi o que
    // travava o modal do /cadastro ate dar F5.
    var newScripts = doc.getElementById('ajax-scripts');
    var currentScripts = document.getElementById('ajax-scripts');
    if (newScripts && currentScripts) {
      currentScripts.innerHTML = newScripts.innerHTML;
      runScripts(currentScripts);
    }

    afterContentReady();
    return true;
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
        window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
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
        window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
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
