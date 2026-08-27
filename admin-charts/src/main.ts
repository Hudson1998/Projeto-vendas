import { ApplicationRef } from '@angular/core';
import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { App } from './app/app';
import { LojaApp } from './app/loja/loja-app';

declare global {
  interface Window {
    __bootstrapAdminCharts?: () => void;
    __ngAppRef?: ApplicationRef;
  }
}

/**
 * O mesmo bundle serve os dois paineis: o do admin monta <app-root> e o do
 * lojista monta <app-loja-root>. Quem decide e o seletor presente na pagina.
 */
function boot(): void {
  const raizAdmin = document.querySelector('app-root');
  const raizLoja = document.querySelector('app-loja-root');

  if (!raizAdmin && !raizLoja) return;

  // a navegacao AJAX troca o #ajax-content sem recarregar: sem destruir a
  // instancia anterior sobrariam duas apps ligadas no mesmo poll de 5s
  if (window.__ngAppRef) {
    window.__ngAppRef.destroy();
    window.__ngAppRef = undefined;
  }

  bootstrapApplication(raizAdmin ? App : LojaApp, appConfig)
    .then((ref) => {
      window.__ngAppRef = ref;
    })
    .catch((err) => console.error(err));
}

window.__bootstrapAdminCharts = boot;
boot();
