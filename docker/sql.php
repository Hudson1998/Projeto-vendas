<?php
/**
 * Shell SQL sem dependencia externa.
 *
 * A imagem php:8.2-apache nao traz o cliente mysql, e o cliente do MariaDB
 * (unico no apt do Debian) nao fala caching_sha2_password, o padrao do
 * MySQL 8. Como o PDO ja esta instalado para a aplicacao, ele resolve.
 *
 *   php docker/sql.php "SELECT id, nome FROM products LIMIT 5"
 *   php docker/sql.php                # modo interativo, uma query por linha
 *   php docker/sql.php --banco=hr_moda_feminina "SELECT COUNT(*) FROM users"
 */
$banco = getenv('DB_DATABASE') ?: 'hr_moda_online';
$args = array_slice($argv, 1);

foreach ($args as $i => $arg) {
    if (str_starts_with($arg, '--banco=')) {
        $banco = substr($arg, 8);
        unset($args[$i]);
    }
}
$query = trim(implode(' ', $args));

$pdo = new PDO("mysql:host=db;dbname={$banco};charset=utf8mb4", 'root', 'root_password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function executar(PDO $pdo, string $sql): void
{
    $sql = trim($sql, " \t\n;");
    if ($sql === '') {
        return;
    }

    try {
        $st = $pdo->query($sql);
    } catch (PDOException $e) {
        echo 'erro: ', $e->getMessage(), PHP_EOL;

        return;
    }

    // INSERT/UPDATE/DELETE nao devolvem colunas
    if ($st->columnCount() === 0) {
        echo $st->rowCount(), ' linha(s) afetada(s)', PHP_EOL;

        return;
    }

    $linhas = $st->fetchAll(PDO::FETCH_ASSOC);
    if (! $linhas) {
        echo '(vazio)', PHP_EOL;

        return;
    }

    // largura de cada coluna = maior valor, com teto para nao estourar a tela
    $larguras = [];
    foreach (array_keys($linhas[0]) as $coluna) {
        $larguras[$coluna] = min(40, max(
            mb_strlen($coluna),
            max(array_map(fn ($l) => mb_strlen((string) ($l[$coluna] ?? 'NULL')), $linhas))
        ));
    }

    $formatar = function (array $valores) use ($larguras): string {
        $celulas = [];
        foreach ($larguras as $coluna => $largura) {
            $valor = (string) ($valores[$coluna] ?? 'NULL');
            if (mb_strlen($valor) > $largura) {
                $valor = mb_substr($valor, 0, $largura - 1).'…';
            }
            $celulas[] = $valor.str_repeat(' ', $largura - mb_strlen($valor));
        }

        return implode('  ', $celulas);
    };

    echo $formatar(array_combine(array_keys($larguras), array_keys($larguras))), PHP_EOL;
    echo str_repeat('-', array_sum($larguras) + 2 * count($larguras)), PHP_EOL;
    foreach ($linhas as $linha) {
        echo $formatar($linha), PHP_EOL;
    }
    echo count($linhas), ' linha(s)', PHP_EOL;
}

if ($query !== '') {
    executar($pdo, $query);
    exit;
}

echo "banco: {$banco} — uma query por linha, Ctrl+D para sair", PHP_EOL;
while (($linha = fgets(STDIN)) !== false) {
    executar($pdo, $linha);
}
