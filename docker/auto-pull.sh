#!/bin/sh
# Mantem o codespace sincronizado com o main sem intervencao manual.
#
# O git so autentica a partir do ambiente do VS Code (o helper em
# /.codespaces/bin nao funciona numa sessao SSH pura), entao quem consegue
# puxar e um processo iniciado de dentro do proprio codespace -- e nao o
# Claude, que entra por fora. Este laco roda em segundo plano e traz cada
# push automaticamente: basta dar F5 no navegador.
#
# --ff-only de proposito: se alguem editar direto no codespace e divergir,
# o pull falha em vez de criar merge sujo. O log conta o que aconteceu.
set -e

INTERVALO="${AUTO_PULL_INTERVALO:-30}"
LOG=/tmp/auto-pull.log
cd /var/www/html

# evita dois lacos simultaneos apos um restart
if [ -f /tmp/auto-pull.pid ] && kill -0 "$(cat /tmp/auto-pull.pid)" 2>/dev/null; then
    echo "auto-pull ja esta rodando (pid $(cat /tmp/auto-pull.pid))"
    exit 0
fi

(
    echo "$$" > /tmp/auto-pull.pid
    while true; do
        antes=$(git rev-parse HEAD 2>/dev/null || echo desconhecido)
        if git pull --ff-only >>"$LOG" 2>&1; then
            depois=$(git rev-parse HEAD 2>/dev/null || echo desconhecido)
            if [ "$antes" != "$depois" ]; then
                echo "$(date '+%H:%M:%S') atualizado: $antes -> $depois" >>"$LOG"
                # views compiladas antigas continuariam sendo servidas
                php artisan view:clear >/dev/null 2>&1 || true
            fi
        fi
        sleep "$INTERVALO"
    done
) >/dev/null 2>&1 &

echo "auto-pull iniciado (a cada ${INTERVALO}s, log em $LOG)"
