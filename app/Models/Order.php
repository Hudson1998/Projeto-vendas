<?php

namespace App\Models;

use App\Classes\Motorista;
use App\Classes\Transportadora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /**
     * As quatro etapas da tela "Acompanhar pedido", na ordem em que aparecem.
     *
     * Nenhuma coluna nova: a primeira sai de status_pagamento e as tres
     * seguintes de status_separacao -- veja etapaAcompanhamento().
     *
     * Eram sete etapas, e as tres que sumiram nao eram esperas de verdade:
     * "pagamento em analise" e "pagamento aprovado" acontecem dentro da
     * primeira, e "pronto pra entrega" e o fim da separacao, nao um estagio
     * proprio. Trilha curta e o que o cliente consegue ler de relance.
     */
    public const ETAPAS_ACOMPANHAMENTO = [
        [
            'rotulo' => 'Aguardando pagamento',
            'chip' => 'Aguardando pagamento',
            'texto' => 'Assim que o pagamento for confirmado, seu pedido segue sozinho para a loja.',
        ],
        [
            'rotulo' => 'Aguardando resposta da loja',
            'chip' => 'Aguardando a loja',
            'texto' => 'Pagamento confirmado. A loja está conferindo a disponibilidade das peças, o que costuma levar até um dia útil.',
        ],
        [
            'rotulo' => 'Pedido em separação',
            'chip' => 'Em separação',
            'texto' => 'Suas peças estão sendo separadas e embaladas no estoque da loja.',
        ],
        [
            'rotulo' => 'Pedido a caminho',
            'chip' => 'A caminho',
            'texto' => 'Saiu para entrega. Você recebe um aviso quando o motorista estiver próximo.',
        ],
    ];

    /**
     * Variacao da primeira etapa quando o cliente ja avisou que pagou.
     *
     * A etapa continua sendo "Aguardando pagamento" -- o dinheiro ainda nao foi
     * confirmado --, mas dizer a quem acabou de pagar que nada foi identificado
     * seria falso. Veja etapaAtual().
     */
    private const ETAPA_PAGAMENTO_EM_ANALISE = [
        'rotulo' => 'Aguardando pagamento',
        'chip' => 'Pagamento em análise',
        'texto' => 'Recebemos o aviso do seu pagamento e ele está em conferência. Assim que o banco confirmar, a loja é avisada automaticamente.',
    ];

    protected $fillable = [
        'user_id',
        'total',
        'distancia_km',
        'valor_frete',
        'status',
        'avaliacao',
        'forma_pagamento',
        'tipo_entrega',
        'endereco_entrega',
        'status_pagamento',
        'comprovante_pagamento_path',
        'ip_compra',
        'localizacao',
        'codigo_pagamento',
        'verificado_banco',
        'status_separacao',
        'entrega_propria',
        'transportadora_id',
        'motorista_id',
        'fragil',
        'dimensoes',
        'analisado_por',
        'analisado_em',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'distancia_km' => 'decimal:2',
        'valor_frete' => 'decimal:2',
        'fragil' => 'boolean',
        'entrega_propria' => 'boolean',
        'verificado_banco' => 'boolean',
        'analisado_em' => 'datetime',
    ];

    /**
     * Indice (0 a 3) da etapa em que o pedido esta agora, ou null quando ele
     * saiu do fluxo -- cancelado ou com pagamento recusado.
     *
     * O pagamento so vale como pago em 'aprovado': 'pendente' e
     * 'aguardando_analise' seguram o pedido na primeira etapa, porque em
     * nenhum dos dois o dinheiro foi confirmado.
     *
     * 'embalado' cai na mesma etapa de 'separado': embalar e o fim da
     * separacao, e o pedido so muda de estagio quando sai para entrega.
     */
    public function etapaAcompanhamento(): ?int
    {
        if ($this->status === 'cancelado' || $this->status_pagamento === 'recusado') {
            return null;
        }

        return match ($this->status_separacao) {
            'enviado' => 3,
            'embalado', 'separado' => 2,
            default => $this->status_pagamento === 'aprovado' ? 1 : 0,
        };
    }

    public function emAcompanhamento(): bool
    {
        return $this->etapaAcompanhamento() !== null;
    }

    /**
     * @return array{rotulo: string, chip: string, texto: string}|null
     */
    public function etapaAtual(): ?array
    {
        $indice = $this->etapaAcompanhamento();

        if ($indice === null) {
            return null;
        }

        if ($indice === 0 && $this->status_pagamento === 'aguardando_analise') {
            return self::ETAPA_PAGAMENTO_EM_ANALISE;
        }

        return self::ETAPAS_ACOMPANHAMENTO[$indice];
    }

    /** Quanto da trilha ja foi percorrido, em porcentagem. */
    public function progressoAcompanhamento(): int
    {
        $indice = $this->etapaAcompanhamento();
        $intervalos = count(self::ETAPAS_ACOMPANHAMENTO) - 1;

        return $indice === null ? 0 : (int) round($indice / $intervalos * 100);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function analisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }

    public function transportadora(): BelongsTo
    {
        return $this->belongsTo(Transportadora::class);
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }
}
