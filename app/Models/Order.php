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
     * As seis etapas da tela "Acompanhar pedido", na ordem em que aparecem.
     *
     * Nenhuma coluna nova: as tres primeiras saem de status_pagamento e as
     * tres ultimas de status_separacao -- veja etapaAcompanhamento().
     */
    public const ETAPAS_ACOMPANHAMENTO = [
        [
            'rotulo' => 'Pendente de pagamento',
            'chip' => 'Aguardando pagamento',
            'texto' => 'Estamos aguardando a confirmação do seu pagamento. Assim que cair, a loja é avisada automaticamente.',
        ],
        [
            'rotulo' => 'Pagamento aprovado',
            'chip' => 'Pagamento aprovado',
            'texto' => 'Pagamento confirmado. Seu pedido já foi encaminhado para a loja.',
        ],
        [
            'rotulo' => 'Aguardando resposta da loja',
            'chip' => 'Aguardando a loja',
            'texto' => 'A loja está confirmando a disponibilidade das peças. Isso costuma levar até um dia útil.',
        ],
        [
            'rotulo' => 'Pedido em separação',
            'chip' => 'Em separação',
            'texto' => 'Suas peças estão sendo separadas e conferidas no estoque da loja.',
        ],
        [
            'rotulo' => 'Pedido pronto pra entrega',
            'chip' => 'Pronto pra entrega',
            'texto' => 'Pedido embalado e aguardando a coleta da transportadora.',
        ],
        [
            'rotulo' => 'Pedido a caminho',
            'chip' => 'A caminho',
            'texto' => 'Saiu para entrega. Você recebe um aviso quando o motorista estiver próximo.',
        ],
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
     * Indice (0 a 5) da etapa em que o pedido esta agora, ou null quando ele
     * saiu do fluxo -- cancelado ou com pagamento recusado.
     *
     * "Pagamento aprovado" e um marco instantaneo, nunca uma espera: assim que
     * o pagamento e aprovado o pedido ja passa a aguardar a loja (etapa 2) e a
     * etapa 1 aparece como concluida.
     */
    public function etapaAcompanhamento(): ?int
    {
        if ($this->status === 'cancelado' || $this->status_pagamento === 'recusado') {
            return null;
        }

        return match ($this->status_separacao) {
            'enviado' => 5,
            'embalado' => 4,
            'separado' => 3,
            // 'pendente' e 'aguardando_analise' sao ambos espera de pagamento
            default => $this->status_pagamento === 'aprovado' ? 2 : 0,
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

        return $indice === null ? null : self::ETAPAS_ACOMPANHAMENTO[$indice];
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
