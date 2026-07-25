(function () {
  function autoDismiss(selector, delay) {
    document.querySelectorAll(selector).forEach((el) => {
      if (el.dataset.flashScheduled) return;
      el.dataset.flashScheduled = '1';
      setTimeout(() => {
        el.classList.add('is-dismissing');
        setTimeout(() => el.remove(), 400);
      }, delay);
    });
  }

  function initFlash() {
    autoDismiss('.flash-status', 3000);
    autoDismiss('.form-status:not(.form-status--error)', 3000);
  }

  if (window.registerPageInit) {
    window.registerPageInit(initFlash);
  } else {
    initFlash();
  }
})();
