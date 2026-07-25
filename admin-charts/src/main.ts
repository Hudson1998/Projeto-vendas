import { ApplicationRef } from '@angular/core';
import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { App } from './app/app';

declare global {
  interface Window {
    __bootstrapAdminCharts?: () => void;
    __ngAppRef?: ApplicationRef;
  }
}

function boot(): void {
  if (!document.querySelector('app-root')) return;

  if (window.__ngAppRef) {
    window.__ngAppRef.destroy();
    window.__ngAppRef = undefined;
  }

  bootstrapApplication(App, appConfig)
    .then((ref) => {
      window.__ngAppRef = ref;
    })
    .catch((err) => console.error(err));
}

window.__bootstrapAdminCharts = boot;
boot();
