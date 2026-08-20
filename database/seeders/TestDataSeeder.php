<?php

namespace Database\Seeders;

use App\Classes\Loja;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Contas de teste para explorar o sistema em desenvolvimento.
 *
 * Nao roda junto do DatabaseSeeder: chame explicitamente com
 * `php artisan db:seed --class=TestDataSeeder`.
 */
class TestDataSeeder extends Seeder
{
    private const SENHA = 'Teste@1234';

    private const EMAILS_CLIENTES = [
        'ana@teste.com',
        'beatriz@teste.com',
        'carla@teste.com',
    ];

    public function run(): void
    {
        $this->criarClientes();

        $loja = $this->criarLojista();
        $this->vincularProdutos($loja);

        $this->criarPedidos();
    }

    private function criarClientes(): void
    {
        $clientes = [
            ['Ana Ribeiro', self::EMAILS_CLIENTES[0], 'Rua das Acacias, 120 - Centro, Sao Paulo - SP'],
            ['Beatriz Nunes', self::EMAILS_CLIENTES[1], 'Av. Paulista, 900, ap 52 - Bela Vista, Sao Paulo - SP'],
            ['Carla Menezes', self::EMAILS_CLIENTES[2], 'Rua Sete de Setembro, 45 - Copacabana, Rio de Janeiro - RJ'],
        ];

        foreach ($clientes as [$nome, $email, $endereco]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nome,
                    'password' => self::SENHA,
                    'role' => 'cliente',
                    'endereco' => $endereco,
                ]
            );
        }
    }

    private function criarLojista(): Loja
    {
        $user = User::updateOrCreate(
            ['email' => 'lojista@teste.com'],
            [
                'name' => 'Daniela Torres',
                'password' => self::SENHA,
                'role' => 'lojista',
                'endereco' => null,
            ]
        );

        // O cadastro real exige upload de KYC e as colunas sao NOT NULL,
        // entao geramos arquivos de marcacao no mesmo disco que o controller usa.
        $pasta = "lojistas-kyc/{$user->id}";
        $documentos = [];

        foreach (['documento-identidade', 'selfie-documento', 'comprovante-endereco'] as $nome) {
            $caminho = "{$pasta}/{$nome}.txt";
            Storage::disk('local')->put($caminho, "Arquivo de teste - {$nome}");
            $documentos[$nome] = $caminho;
        }

        return Loja::updateOrCreate(
            ['user_id' => $user->id],
            [
                'tipo_pessoa' => 'juridica',
                'cnpj' => '11222333000181',
                'razao_social' => 'Torres Confeccoes LTDA',
                'nome_fantasia' => 'Ateliê Torres',
                'nome_exibicao' => 'Ateliê Torres',
                'inscricao_estadual' => '110042490114',
                'ie_isento' => false,
                'regime_tributario' => 'simples_nacional',

                'nome_responsavel' => $user->name,
                'email' => $user->email,
                'whatsapp' => '(11) 98888-1234',

                'fiscal_cep' => '01310-100',
                'fiscal_rua' => 'Av. Paulista',
                'fiscal_numero' => '1578',
                'fiscal_bairro' => 'Bela Vista',
                'fiscal_cidade' => 'Sao Paulo',
                'fiscal_estado' => 'SP',

                'envio_cep' => '01310-100',
                'envio_rua' => 'Av. Paulista',
                'envio_numero' => '1578',
                'envio_bairro' => 'Bela Vista',
                'envio_cidade' => 'Sao Paulo',
                'envio_estado' => 'SP',

                'banco' => 'Banco do Brasil',
                'agencia' => '1234-5',
                'conta' => '98765-4',
                'titular_conta' => 'Torres Confeccoes LTDA',
                'chave_pix' => 'lojista@teste.com',

                'bio_loja' => 'Peças de alfaiataria e vestidos produzidos em pequenos lotes.',

                'prazo_expedicao_dias_uteis' => 3,
                'politica_troca_devolucao' => 'Trocas e devolucoes em ate 7 dias corridos apos o recebimento, com a peca sem uso e etiqueta.',

                'documento_identidade_path' => $documentos['documento-identidade'],
                'selfie_documento_path' => $documentos['selfie-documento'],
                'comprovante_endereco_path' => $documentos['comprovante-endereco'],

                'nivel_acesso' => 'padrao',
            ]
        );
    }

    /**
     * Sem produtos vinculados o painel do lojista nao mostra nada,
     * entao adotamos os produtos que ainda estao sem loja.
     */
    private function vincularProdutos(Loja $loja): void
    {
        Product::whereNull('loja_id')->update(['loja_id' => $loja->id]);
    }

    /**
     * Pedidos espalhados pelos ultimos 30 dias, com uma mistura de estados
     * para que os graficos, o faturamento e a fila de analise tenham conteudo.
     *
     * Os produtos sao resolvidos pelo nome porque os ids mudam a cada reseed.
     */
    private function criarPedidos(): void
    {
        $clientes = User::whereIn('email', self::EMAILS_CLIENTES)->get()->keyBy('email');
        $produtos = Product::all()->keyBy('nome');

        if ($clientes->isEmpty() || $produtos->isEmpty()) {
            return;
        }

        $this->limparPedidosAnteriores($clientes->pluck('id')->all());

        // [email, dias atras, status, status_pagamento, status_separacao, avaliacao, forma, itens]
        $pedidos = [
            ['ana@teste.com', 28, 'concluido', 'aprovado', 'enviado', 5, 'pix', [
                ['Vestido Midi Preto', 1, 'M', 'Preto'],
                ['Cinto Fivela Dourada', 1, 'U', 'Dourado'],
            ]],
            ['beatriz@teste.com', 24, 'concluido', 'aprovado', 'enviado', 4, 'cartao', [
                ['Camisa Alfaiataria', 2, 'P', 'Off-White'],
            ]],
            ['carla@teste.com', 19, 'concluido', 'aprovado', 'enviado', 5, 'pix', [
                ['Vestido Midi Preto', 1, 'G', 'Preto'],
                ['Saia Plissada', 1, 'M', 'Bege'],
            ]],
            ['ana@teste.com', 14, 'concluido', 'aprovado', 'embalado', null, 'boleto', [
                ['Bolsa Estruturada', 1, 'U', 'Caramelo'],
            ]],
            ['beatriz@teste.com', 9, 'concluido', 'aprovado', 'separado', 3, 'cartao', [
                ['Vestido Midi Preto', 1, 'P', 'Preto'],
                ['Calça Pantalona', 1, 'M', 'Preto'],
            ]],
            ['carla@teste.com', 5, 'concluido', 'aguardando_analise', null, null, 'pix', [
                ['Vestido Longo Cetim', 1, 'M', 'Vinho'],
            ]],
            ['ana@teste.com', 2, 'concluido', 'aguardando_analise', null, null, 'boleto', [
                ['Camisa de Seda Off-White', 1, 'M', 'Off-White'],
                ['Saia Lápis Cinza', 1, 'P', 'Cinza'],
            ]],
            ['beatriz@teste.com', 6, 'cancelado', 'recusado', null, null, 'cartao', [
                ['Vestido Tubinho', 1, 'G', 'Preto'],
            ]],
        ];

        foreach ($pedidos as [$email, $dias, $status, $pagamento, $separacao, $avaliacao, $forma, $itens]) {
            $cliente = $clientes->get($email);

            if (! $cliente) {
                continue;
            }

            $linhas = $this->resolverItens($itens, $produtos);

            if ($linhas === []) {
                continue;
            }

            $frete = 19.90;
            $subtotal = array_sum(array_map(
                fn (array $linha) => $linha['preco_unitario'] * $linha['quantidade'],
                $linhas
            ));

            $data = now()->subDays($dias);

            $order = Order::create([
                'user_id' => $cliente->id,
                'total' => round($subtotal + $frete, 2),
                'distancia_km' => 12.5,
                'valor_frete' => $frete,
                'status' => $status,
                'avaliacao' => $avaliacao,
                'forma_pagamento' => $forma,
                'tipo_entrega' => 'entrega',
                'endereco_entrega' => $cliente->endereco,
                'status_pagamento' => $pagamento,
                'status_separacao' => $separacao,
                'codigo_pagamento' => strtoupper($forma).'-TESTE-'.$data->format('dmY'),
                'verificado_banco' => $pagamento === 'aprovado',
                'ip_compra' => '127.0.0.1',
                'localizacao' => $cliente->endereco,
                'entrega_propria' => true,
                'fragil' => false,
                'created_at' => $data,
                'updated_at' => $data,
            ]);

            foreach ($linhas as $linha) {
                OrderItem::create($linha + ['order_id' => $order->id]);
            }
        }
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2: string, 3: string}>  $itens
     * @return array<int, array<string, mixed>>
     */
    private function resolverItens(array $itens, $produtos): array
    {
        $linhas = [];

        foreach ($itens as [$nome, $quantidade, $tamanho, $cor]) {
            $produto = $produtos->get($nome);

            if (! $produto) {
                continue;
            }

            $linhas[] = [
                'product_id' => $produto->id,
                'quantidade' => $quantidade,
                'preco_unitario' => $produto->preco,
                'tamanho' => $tamanho,
                'cor' => $cor,
            ];
        }

        return $linhas;
    }

    /**
     * @param  array<int, int>  $clienteIds
     */
    private function limparPedidosAnteriores(array $clienteIds): void
    {
        $pedidoIds = Order::whereIn('user_id', $clienteIds)->pluck('id');

        OrderItem::whereIn('order_id', $pedidoIds)->delete();
        Order::whereIn('id', $pedidoIds)->delete();
    }
}
