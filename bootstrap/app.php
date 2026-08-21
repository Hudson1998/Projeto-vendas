<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Confia no cabecalho X-Forwarded-Proto do proxy que termina o TLS.
        // Sem isso, atras de um proxy https (Codespaces, load balancer) o
        // Laravel enxerga a requisicao como http e asset()/url() devolvem
        // links http dentro de uma pagina https -- o navegador bloqueia como
        // conteudo misto e a loja abre sem imagens e sem o JS do admin.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'lojista' => \App\Http\Middleware\EnsureUserIsLojista::class,
            'log.visit' => \App\Http\Middleware\LogPageVisit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
