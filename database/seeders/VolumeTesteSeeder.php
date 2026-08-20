<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Massa de dados para testar o sistema em volume: 50 lojas, 100 clientes,
 * transportadoras com motoristas e o historico de compra, pagamento,
 * favoritos e entrega ligado a tudo isso.
 *
 * Nao existe tabela de transacoes separada neste projeto -- o historico de
 * pagamento sao as colunas forma_pagamento, status_pagamento, codigo_pagamento
 * e verificado_banco em orders; a entrega sao status_separacao, entrega_propria,
 * transportadora_id e motorista_id na mesma tabela.
 *
 * Usa insercao em lote (nao Eloquent linha a linha) porque sao milhares de
 * registros e a maquina de desenvolvimento e limitada.
 *
 * Rode com `php artisan db:seed --class=VolumeTesteSeeder`.
 */
class VolumeTesteSeeder extends Seeder
{
    private const SENHA = 'Teste@1234';

    private const QTD_CLIENTES = 100;

    private const QTD_LOJAS = 50;

    private const SUFIXO_CLIENTE = '@cliente.teste';

    private const SUFIXO_LOJISTA = '@loja.teste';

    /** Uma unica hash reaproveitada: bcrypt e caro e sao 150 contas. */
    private string $hash;

    private array $cidades = [
        ['São Paulo', 'SP'], ['Rio de Janeiro', 'RJ'], ['Belo Horizonte', 'MG'],
        ['Curitiba', 'PR'], ['Porto Alegre', 'RS'], ['Salvador', 'BA'],
        ['Recife', 'PE'], ['Fortaleza', 'CE'], ['Goiânia', 'GO'],
        ['Florianópolis', 'SC'], ['Campinas', 'SP'], ['Belém', 'PA'],
    ];

    private array $nomes = [
        'Ana', 'Beatriz', 'Camila', 'Daniela', 'Eduarda', 'Fernanda', 'Gabriela',
        'Helena', 'Isabela', 'Juliana', 'Karina', 'Larissa', 'Mariana', 'Natália',
        'Olívia', 'Patrícia', 'Renata', 'Sabrina', 'Tatiana', 'Vanessa',
        'Bruno', 'Carlos', 'Diego', 'Eduardo', 'Felipe', 'Gustavo', 'Henrique',
        'Igor', 'João', 'Lucas', 'Marcelo', 'Nelson', 'Otávio', 'Paulo', 'Rafael',
    ];

    private array $sobrenomes = [
        'Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves',
        'Pereira', 'Lima', 'Gomes', 'Costa', 'Ribeiro', 'Martins', 'Carvalho',
        'Almeida', 'Lopes', 'Soares', 'Fernandes', 'Vieira', 'Barbosa',
    ];

    private array $marcas = [
        'Atelier', 'Casa', 'Studio', 'Boutique', 'Maison', 'Ateliê', 'Loja',
    ];

    private array $marcasSufixo = [
        'Aurora', 'Bourbon', 'Cravo', 'Dália', 'Estella', 'Flor de Sal', 'Giro',
        'Habana', 'Íris', 'Jasmim', 'Kiara', 'Lumen', 'Malva', 'Nuvem', 'Ocre',
        'Pérola', 'Quartzo', 'Rosé', 'Sereno', 'Tulipa', 'Urbana', 'Verbena',
        'Willow', 'Xisto', 'Yara', 'Zenite', 'Âmbar', 'Bruma', 'Cedro', 'Duna',
        'Eclipse', 'Fauna', 'Gaia', 'Horizonte', 'Indigo', 'Jade', 'Linho',
        'Miragem', 'Norte', 'Orvalho', 'Prisma', 'Quinta', 'Rubi', 'Sável',
        'Terra', 'Umbra', 'Vento', 'Zibelina', 'Alecrim', 'Bossa',
    ];

    public function run(): void
    {
        mt_srand(20260820); // massa deterministica: reexecutar da o mesmo resultado

        $this->hash = Hash::make(self::SENHA);

        $this->limpar();

        $lojistas = $this->criarLojistas();
        $lojaIds = $this->criarLojas($lojistas);
        $this->distribuirProdutos($lojaIds);

        [$transportadoras, $motoristas] = $this->criarLogistica($lojaIds);

        $clienteIds = $this->criarClientes();

        $this->criarHistorico($clienteIds, $transportadoras, $motoristas);
        $this->criarFavoritos($clienteIds);
    }

    /** Remove a massa anterior deste seeder, preservando os dados manuais. */
    private function limpar(): void
    {
        $userIds = DB::table('users')
            ->where('email', 'like', '%'.self::SUFIXO_CLIENTE)
            ->orWhere('email', 'like', '%'.self::SUFIXO_LOJISTA)
            ->pluck('id');

        if ($userIds->isNotEmpty()) {
            $orderIds = DB::table('orders')->whereIn('user_id', $userIds)->pluck('id');

            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->whereIn('id', $orderIds)->delete();
            DB::table('favorites')->whereIn('user_id', $userIds)->delete();
            DB::table('cart_items')->whereIn('user_id', $userIds)->delete();
            DB::table('product_reviews')->whereIn('user_id', $userIds)->delete();

            // solta os produtos das lojas que serao removidas
            $lojaIds = DB::table('lojas')->whereIn('user_id', $userIds)->pluck('id');
            DB::table('products')->whereIn('loja_id', $lojaIds)->update(['loja_id' => null]);
            DB::table('loja_transportadora')->whereIn('loja_id', $lojaIds)->delete();
            DB::table('lojas')->whereIn('id', $lojaIds)->delete();

            DB::table('users')->whereIn('id', $userIds)->delete();
        }

        DB::table('motoristas')->where('cnh', 'like', 'TESTE-%')->delete();
        DB::table('transportadoras')->where('cnpj', 'like', '99%')->delete();
    }

    /** @return array<int, int> ids dos usuarios lojistas */
    private function criarLojistas(): array
    {
        $linhas = [];
        $agora = now();

        for ($i = 1; $i <= self::QTD_LOJAS; $i++) {
            $linhas[] = [
                'name' => $this->pessoa($i * 3),
                'email' => sprintf('lojista%02d%s', $i, self::SUFIXO_LOJISTA),
                'password' => $this->hash,
                'role' => 'lojista',
                'endereco' => null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }

        DB::table('users')->insert($linhas);

        return DB::table('users')
            ->where('email', 'like', '%'.self::SUFIXO_LOJISTA)
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, int>  $lojistas
     * @return array<int, int> ids das lojas
     */
    private function criarLojas(array $lojistas): array
    {
        $linhas = [];
        $agora = now();

        foreach ($lojistas as $i => $userId) {
            [$cidade, $uf] = $this->cidades[$i % count($this->cidades)];
            $marca = $this->marcas[$i % count($this->marcas)].' '.$this->marcasSufixo[$i % count($this->marcasSufixo)];
            $juridica = $i % 3 !== 0;
            $responsavel = $this->pessoa($i * 3);

            $linhas[] = [
                'user_id' => $userId,
                'tipo_pessoa' => $juridica ? 'juridica' : 'fisica',
                'cpf' => $juridica ? null : $this->digitos(11, $i),
                'cnpj' => $juridica ? $this->digitos(14, $i) : null,
                'razao_social' => $juridica ? $marca.' Confecções LTDA' : null,
                'nome_fantasia' => $marca,
                'nome_exibicao' => $marca,
                'inscricao_estadual' => $juridica ? $this->digitos(12, $i + 7) : null,
                'ie_isento' => ! $juridica,
                'regime_tributario' => $juridica ? 'simples_nacional' : null,
                'nome_responsavel' => $responsavel,
                'email' => sprintf('lojista%02d%s', $i + 1, self::SUFIXO_LOJISTA),
                'whatsapp' => sprintf('(%02d) 9%04d-%04d', 11 + ($i % 79), 1000 + $i * 7 % 8999, 1000 + $i * 13 % 8999),
                'fiscal_cep' => sprintf('%05d-%03d', 1000 + $i * 37 % 88999, $i * 7 % 999),
                'fiscal_rua' => 'Rua '.$this->sobrenomes[$i % count($this->sobrenomes)],
                'fiscal_numero' => (string) (50 + $i * 13 % 1900),
                'fiscal_complemento' => $i % 4 === 0 ? 'Sala '.(10 + $i % 90) : null,
                'fiscal_bairro' => 'Centro',
                'fiscal_cidade' => $cidade,
                'fiscal_estado' => $uf,
                'envio_cep' => sprintf('%05d-%03d', 1000 + $i * 37 % 88999, $i * 7 % 999),
                'envio_rua' => 'Rua '.$this->sobrenomes[$i % count($this->sobrenomes)],
                'envio_numero' => (string) (50 + $i * 13 % 1900),
                'envio_complemento' => null,
                'envio_bairro' => 'Centro',
                'envio_cidade' => $cidade,
                'envio_estado' => $uf,
                'banco' => ['Banco do Brasil', 'Itaú', 'Bradesco', 'Nubank', 'Caixa'][$i % 5],
                'agencia' => sprintf('%04d', 1000 + $i * 3 % 8999),
                'conta' => sprintf('%05d-%d', 10000 + $i * 11 % 89999, $i % 10),
                'titular_conta' => $responsavel,
                'chave_pix' => sprintf('lojista%02d%s', $i + 1, self::SUFIXO_LOJISTA),
                'logotipo' => null,
                'bio_loja' => 'Peças selecionadas de '.$cidade.', com produção em pequenos lotes.',
                'prazo_expedicao_dias_uteis' => 1 + $i % 7,
                'politica_troca_devolucao' => 'Trocas em até 7 dias corridos após o recebimento, com a peça sem uso e etiqueta.',
                'documento_identidade_path' => "lojistas-kyc/{$userId}/documento-identidade.txt",
                'selfie_documento_path' => "lojistas-kyc/{$userId}/selfie-documento.txt",
                'contrato_social_mei_path' => $juridica ? "lojistas-kyc/{$userId}/contrato-social.txt" : null,
                'comprovante_endereco_path' => "lojistas-kyc/{$userId}/comprovante-endereco.txt",
                'comprovante_cnpj_path' => null,
                'nivel_acesso' => 'padrao',
                'plano_id' => null,
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }

        foreach (array_chunk($linhas, 25) as $lote) {
            DB::table('lojas')->insert($lote);
        }

        return DB::table('lojas')->whereIn('user_id', $lojistas)->orderBy('id')->pluck('id')->all();
    }

    /** Espalha o catalogo entre as primeiras lojas para o marketplace ter varios vendedores. */
    private function distribuirProdutos(array $lojaIds): void
    {
        $produtos = DB::table('products')->orderBy('id')->pluck('id')->all();
        $quantasLojas = min(10, count($lojaIds));

        foreach ($produtos as $i => $produtoId) {
            DB::table('products')
                ->where('id', $produtoId)
                ->update(['loja_id' => $lojaIds[$i % $quantasLojas]]);
        }
    }

    /** @return array{0: array<int,int>, 1: array<int, array<int,int>>} */
    private function criarLogistica(array $lojaIds): array
    {
        $agora = now();
        $nomes = ['Rota Sul', 'ExpressoBR', 'Via Norte', 'LogFácil', 'TransAtlas', 'Entrega Já', 'CargoLine', 'RedeVeloz'];
        $linhas = [];

        foreach ($nomes as $i => $nome) {
            $linhas[] = [
                'nome' => $nome,
                'cnpj' => '99'.$this->digitos(12, $i + 3),
                'telefone' => sprintf('(%02d) 3%03d-%04d', 11 + $i, 100 + $i * 9, 1000 + $i * 137 % 8999),
                'email' => 'contato@'.strtolower(str_replace(' ', '', $nome)).'.teste',
                'tipo_veiculo' => ['Van', 'Caminhão leve', 'Moto', 'Utilitário'][$i % 4],
                'area_atuacao' => ['Sudeste', 'Sul', 'Nordeste', 'Nacional'][$i % 4],
                'ativo' => $i % 7 !== 6,
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }

        DB::table('transportadoras')->insert($linhas);
        $transportadoras = DB::table('transportadoras')->where('cnpj', 'like', '99%')->orderBy('id')->pluck('id')->all();

        $motoristas = [];
        $linhasMotorista = [];

        foreach ($transportadoras as $i => $transportadoraId) {
            for ($m = 0; $m < 3; $m++) {
                $linhasMotorista[] = [
                    'transportadora_id' => $transportadoraId,
                    'nome' => $this->pessoa($i * 5 + $m * 11),
                    'cpf' => $this->digitos(11, $i * 5 + $m),
                    'cnh' => sprintf('TESTE-%03d', $i * 3 + $m),
                    'telefone' => sprintf('(%02d) 9%04d-%04d', 11 + $i, 2000 + $m * 137, 3000 + $i * 91 % 6999),
                    'ativo' => true,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }
        }

        DB::table('motoristas')->insert($linhasMotorista);

        foreach ($transportadoras as $transportadoraId) {
            $motoristas[$transportadoraId] = DB::table('motoristas')
                ->where('transportadora_id', $transportadoraId)
                ->pluck('id')
                ->all();
        }

        // vincula transportadoras as lojas que tem produto
        $vinculos = [];
        foreach (array_slice($lojaIds, 0, 10) as $i => $lojaId) {
            foreach ([$transportadoras[$i % count($transportadoras)], $transportadoras[($i + 3) % count($transportadoras)]] as $tid) {
                $vinculos[$lojaId.'-'.$tid] = [
                    'loja_id' => $lojaId,
                    'transportadora_id' => $tid,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }
        }

        DB::table('loja_transportadora')->insert(array_values($vinculos));

        return [$transportadoras, $motoristas];
    }

    /** @return array<int, int> */
    private function criarClientes(): array
    {
        $linhas = [];
        $agora = now();

        for ($i = 1; $i <= self::QTD_CLIENTES; $i++) {
            [$cidade, $uf] = $this->cidades[$i % count($this->cidades)];

            $linhas[] = [
                'name' => $this->pessoa($i),
                'email' => sprintf('cliente%03d%s', $i, self::SUFIXO_CLIENTE),
                'password' => $this->hash,
                'role' => 'cliente',
                'endereco' => sprintf(
                    'Rua %s, %d - %s, %s - %s',
                    $this->sobrenomes[$i % count($this->sobrenomes)],
                    20 + $i * 17 % 1500,
                    'Centro',
                    $cidade,
                    $uf
                ),
                'created_at' => now()->subDays(300 - $i * 2),
                'updated_at' => $agora,
            ];
        }

        foreach (array_chunk($linhas, 50) as $lote) {
            DB::table('users')->insert($lote);
        }

        return DB::table('users')
            ->where('email', 'like', '%'.self::SUFIXO_CLIENTE)
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /**
     * Historico de compra + pagamento + entrega, espalhado por 12 meses.
     *
     * @param  array<int, int>  $clienteIds
     * @param  array<int, int>  $transportadoras
     * @param  array<int, array<int,int>>  $motoristas
     */
    private function criarHistorico(array $clienteIds, array $transportadoras, array $motoristas): void
    {
        $produtos = DB::table('products')->select('id', 'preco', 'preco_promocional')->get()->all();
        $variantes = DB::table('product_variants')
            ->select('product_id', 'tamanho', 'cor')
            ->get()
            ->groupBy('product_id');

        if ($produtos === [] || $clienteIds === []) {
            return;
        }

        $enderecos = DB::table('users')->whereIn('id', $clienteIds)->pluck('endereco', 'id');

        $pedidos = [];
        $itensPorPedido = [];

        foreach ($clienteIds as $indiceCliente => $clienteId) {
            $quantos = 1 + ($indiceCliente * 7) % 6; // de 1 a 6 pedidos por cliente

            for ($p = 0; $p < $quantos; $p++) {
                $diasAtras = ($indiceCliente * 11 + $p * 43) % 365;
                $data = now()->subDays($diasAtras)->subHours(($indiceCliente + $p) % 24);

                // itens
                $qtdItens = 1 + ($indiceCliente + $p) % 3;
                $itens = [];
                $subtotal = 0.0;

                for ($it = 0; $it < $qtdItens; $it++) {
                    $produto = $produtos[($indiceCliente * 5 + $p * 3 + $it * 7) % count($produtos)];
                    $quantidade = 1 + ($it + $p) % 2;
                    $preco = (float) ($produto->preco_promocional ?? $produto->preco);

                    $vs = $variantes->get($produto->id);
                    $v = $vs && $vs->count() > 0 ? $vs[($indiceCliente + $it) % $vs->count()] : null;

                    $itens[] = [
                        'product_id' => $produto->id,
                        'quantidade' => $quantidade,
                        'preco_unitario' => $preco,
                        'tamanho' => $v->tamanho ?? null,
                        'cor' => $v->cor ?? null,
                    ];

                    $subtotal += $preco * $quantidade;
                }

                $frete = [0.0, 14.90, 19.90, 24.90][($indiceCliente + $p) % 4];

                // situacao coerente com a idade do pedido
                $sorte = ($indiceCliente * 3 + $p * 17) % 20;

                if ($sorte === 0) {
                    $status = 'cancelado';
                    $statusPagamento = 'recusado';
                    $separacao = null;
                } elseif ($sorte <= 2 && $diasAtras < 10) {
                    $status = 'concluido';
                    $statusPagamento = 'aguardando_analise';
                    $separacao = null;
                } else {
                    $status = 'concluido';
                    $statusPagamento = 'aprovado';
                    $separacao = $diasAtras > 20 ? 'enviado' : (['separado', 'embalado', 'enviado'][$diasAtras % 3]);
                }

                $forma = ['pix', 'cartao', 'boleto'][($indiceCliente + $p) % 3];
                $propria = ($indiceCliente + $p) % 4 === 0;
                $transportadoraId = null;
                $motoristaId = null;

                if (! $propria && $separacao !== null) {
                    $transportadoraId = $transportadoras[($indiceCliente + $p) % count($transportadoras)];
                    $lista = $motoristas[$transportadoraId] ?? [];
                    $motoristaId = $lista === [] ? null : $lista[($indiceCliente + $p) % count($lista)];
                }

                $pedidos[] = [
                    'user_id' => $clienteId,
                    'total' => round($subtotal + $frete, 2),
                    'distancia_km' => round(3 + (($indiceCliente * 7 + $p * 13) % 400) / 10, 2),
                    'valor_frete' => $frete,
                    'status' => $status,
                    'avaliacao' => $status === 'concluido' && $separacao === 'enviado'
                        ? [null, 3, 4, 4, 5, 5, 5][($indiceCliente + $p) % 7]
                        : null,
                    'forma_pagamento' => $forma,
                    'tipo_entrega' => $propria ? 'retirada' : 'entrega',
                    'endereco_entrega' => $enderecos[$clienteId] ?? null,
                    'status_pagamento' => $statusPagamento,
                    'comprovante_pagamento_path' => null,
                    'ip_compra' => '198.51.100.'.(1 + ($indiceCliente % 250)),
                    'localizacao' => $enderecos[$clienteId] ?? null,
                    'codigo_pagamento' => strtoupper($forma).'-'.$data->format('Ymd').'-'.str_pad((string) (($indiceCliente * 31 + $p) % 9999), 4, '0', STR_PAD_LEFT),
                    'verificado_banco' => $statusPagamento === 'aprovado',
                    'status_separacao' => $separacao,
                    'entrega_propria' => $propria,
                    'transportadora_id' => $transportadoraId,
                    'motorista_id' => $motoristaId,
                    'fragil' => ($indiceCliente + $p) % 9 === 0,
                    'dimensoes' => ['30x20x10', '40x30x15', '25x18x8'][($indiceCliente + $p) % 3],
                    'analisado_por' => null,
                    'analisado_em' => $statusPagamento === 'aprovado' ? $data->copy()->addHours(2) : null,
                    'created_at' => $data,
                    'updated_at' => $data,
                ];

                $itensPorPedido[] = $itens;
            }
        }

        // insere pedidos em lote e recupera os ids na mesma ordem
        $primeiroId = DB::table('orders')->max('id') ?? 0;

        foreach (array_chunk($pedidos, 100) as $lote) {
            DB::table('orders')->insert($lote);
        }

        $ids = DB::table('orders')->where('id', '>', $primeiroId)->orderBy('id')->pluck('id')->all();

        $linhasItens = [];
        $agora = now();

        foreach ($ids as $posicao => $orderId) {
            foreach ($itensPorPedido[$posicao] ?? [] as $item) {
                $linhasItens[] = $item + [
                    'order_id' => $orderId,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ];
            }
        }

        foreach (array_chunk($linhasItens, 200) as $lote) {
            DB::table('order_items')->insert($lote);
        }
    }

    /** Historico de likes. */
    private function criarFavoritos(array $clienteIds): void
    {
        $produtos = DB::table('products')->orderBy('id')->pluck('id')->all();

        if ($produtos === []) {
            return;
        }

        $linhas = [];
        $agora = now();

        foreach ($clienteIds as $i => $clienteId) {
            $quantos = ($i * 5) % 8; // de 0 a 7 favoritos

            for ($f = 0; $f < $quantos; $f++) {
                $produtoId = $produtos[($i * 3 + $f * 7) % count($produtos)];
                $linhas[$clienteId.'-'.$produtoId] = [
                    'user_id' => $clienteId,
                    'product_id' => $produtoId,
                    'created_at' => now()->subDays(($i + $f * 3) % 200),
                    'updated_at' => $agora,
                ];
            }
        }

        foreach (array_chunk(array_values($linhas), 200) as $lote) {
            DB::table('favorites')->insert($lote);
        }
    }

    private function pessoa(int $semente): string
    {
        return $this->nomes[$semente % count($this->nomes)]
            .' '.$this->sobrenomes[($semente * 3) % count($this->sobrenomes)];
    }

    private function digitos(int $quantidade, int $semente): string
    {
        $s = '';

        for ($i = 0; $i < $quantidade; $i++) {
            $s .= (($semente * 7 + $i * 13 + 3) % 10);
        }

        return $s;
    }
}
