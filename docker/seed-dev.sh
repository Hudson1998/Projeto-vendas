#!/bin/sh
# Popula um banco recem-criado com a massa de desenvolvimento.
#
# Usado pelo postCreateCommand do Codespaces, onde o volume do MySQL nasce
# vazio e a loja abriria sem nenhum produto. Tambem serve localmente para
# recriar a massa do zero.
#
# A ordem respeita as dependencias declaradas nos proprios seeders:
# InteracoesTesteSeeder precisa de TestDataSeeder (clientes) e de
# CatalogoTesteSeeder (produtos e variantes).
set -e

cd /var/www/html

# o postCreateCommand dispara assim que o container sobe, mas o entrypoint ainda
# pode estar rodando o composer install + migrate (leva minutos no primeiro
# boot). Sem esperar, os seeders quebram com "table not found".
echo ">> aguardando o entrypoint terminar composer install + migrate"
tentativas=0
# "migrate:status" passa a funcionar assim que a tabela migrations existe, ou
# seja, no meio da migracao. Esperar so por ele deixava os seeders comecarem
# com parte do schema no lugar -- o CatalogoTesteSeeder morria com "Unknown
# column 'preco_promocional'" porque a migration dela ainda nao tinha rodado.
# Por isso a espera exige tambem que nao reste nenhuma migration pendente.
until php artisan migrate:status > /tmp/migrate-status.txt 2>&1 && ! grep -qi "pending" /tmp/migrate-status.txt; do
    tentativas=$((tentativas + 1))
    if [ "$tentativas" -gt 120 ]; then
        echo "!! migrations nao terminaram em 6 minutos; ultimo status:"
        cat /tmp/migrate-status.txt
        exit 1
    fi
    sleep 3
done

echo ">> produtos base + admin"
php artisan db:seed --force

echo ">> catalogo de teste com variantes"
php artisan db:seed --class=CatalogoTesteSeeder --force

echo ">> catalogo completo (500 produtos)"
php artisan db:seed --class=CatalogoQuinhentosSeeder --force

echo ">> contas de teste"
php artisan db:seed --class=TestDataSeeder --force

echo ">> massa de volume (lojas, clientes, pedidos)"
php artisan db:seed --class=VolumeTesteSeeder --force

echo ">> interacoes (favoritos, avaliacoes, buscas)"
php artisan db:seed --class=InteracoesTesteSeeder --force

# o postCreateCommand roda como root: sem isso os arquivos que os seeders
# tocarem em storage/ ficam com dono root e o Apache (www-data) devolve 500.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo ">> pronto"
