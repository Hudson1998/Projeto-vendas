@extends('layouts.app')

@section('title', 'HR Moda Feminina')

@section('content')

<section class="wrap hero">
  <div class="hero__content">
    <div class="eyebrow">
      <div class="eyebrow__line"></div>
      <span class="eyebrow__label">Nova coleção</span>
    </div>
    <h1 class="hero__title">Elegância em cada detalhe</h1>
    <p class="hero__text">Peças selecionadas para a mulher que veste sofisticação todos os dias. Do essencial ao statement — tudo em um só lugar.</p>
    <div class="actions">
      <a href="#colecao" class="btn btn-primary">Ver coleção</a>
      <a href="#sobre" class="btn btn-outline">Sobre a HR</a>
    </div>
  </div>
  <img class="hero__image" src="{{ asset('assets/hero.jpg') }}" alt="Look principal">
</section>

<section id="colecao" class="section--dark">
  <div class="wrap">
    <div class="section__header">
      <h2 class="section__title" id="collection-title">Coleção</h2>
      <span class="section__count" id="collection-count"></span>
    </div>
    <div class="product-grid" id="product-grid"></div>
    <div class="empty-state" id="empty-state">
      <span class="empty-state__title">Nenhuma peça encontrada</span>
      <span class="empty-state__subtitle">Tente outra busca ou categoria.</span>
    </div>
  </div>
</section>

<section id="sobre" class="section--bordered">
  <div class="wrap about">
    <img class="about__image" src="{{ asset('assets/sobre.jpg') }}" alt="Loja">
    <div class="about__content">
      <div class="eyebrow">
        <div class="eyebrow__line"></div>
        <span class="eyebrow__label">Sobre</span>
      </div>
      <h2 class="about__title">Moda feita para você</h2>
      <p class="about__text">A HR Moda Feminina nasceu da paixão por vestir mulheres com elegância e autenticidade. Cada peça é escolhida a dedo, priorizando qualidade, caimento e atemporalidade.</p>
      <div class="stats">
        <div class="stat">
          <span class="stat__number">+500</span>
          <span class="stat__label">Clientes felizes</span>
        </div>
        <div class="stat">
          <span class="stat__number">100%</span>
          <span class="stat__label">Peças selecionadas</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="contato" class="section--dark">
  <div class="wrap contact">
    <h2 class="contact__title">Fale com a gente</h2>
    <p class="contact__text">Atendimento personalizado pelo WhatsApp e novidades diárias no Instagram.</p>
    <div class="actions">
      <a href="#" class="btn btn-primary">WhatsApp</a>
      <a href="#" class="btn btn-outline">@@hrmodafeminina</a>
    </div>
  </div>
</section>

@endsection
