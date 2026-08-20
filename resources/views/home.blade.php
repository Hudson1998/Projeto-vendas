@extends('layouts.app')

@section('title', 'HR Moda Online')

@section('content')

<section class="carousel-section" id="secao-mais-comprados">
  <div class="wrap">
    <div class="section__header">
      <h2 class="section__title">Mais comprados</h2>
    </div>
    <div class="carousel">
      <button type="button" class="carousel__arrow" data-carousel-prev="mais-comprados" aria-label="Anterior">&#8249;</button>
      <div class="carousel__track" id="carousel-mais-comprados"></div>
      <button type="button" class="carousel__arrow" data-carousel-next="mais-comprados" aria-label="Próximo">&#8250;</button>
    </div>
  </div>
</section>

<section class="carousel-section" id="secao-mais-visitados">
  <div class="wrap">
    <div class="section__header">
      <h2 class="section__title">Mais visitados</h2>
    </div>
    <div class="carousel">
      <button type="button" class="carousel__arrow" data-carousel-prev="mais-visitados" aria-label="Anterior">&#8249;</button>
      <div class="carousel__track" id="carousel-mais-visitados"></div>
      <button type="button" class="carousel__arrow" data-carousel-next="mais-visitados" aria-label="Próximo">&#8250;</button>
    </div>
  </div>
</section>

<section class="carousel-section" id="secao-promocoes">
  <div class="wrap">
    <div class="section__header">
      <h2 class="section__title">Promoções</h2>
    </div>
    <div class="carousel">
      <button type="button" class="carousel__arrow" data-carousel-prev="promocoes" aria-label="Anterior">&#8249;</button>
      <div class="carousel__track" id="carousel-promocoes"></div>
      <button type="button" class="carousel__arrow" data-carousel-next="promocoes" aria-label="Próximo">&#8250;</button>
    </div>
  </div>
</section>

<section id="colecao" class="section--dark">
  <div class="wrap">
    <div class="section__header">
      <h2 class="section__title" id="collection-title">Coleção</h2>
    </div>
    <div class="product-grid" id="product-grid"></div>
    <div class="empty-state" id="empty-state">
      <span class="empty-state__title">Nenhuma peça encontrada</span>
      <span class="empty-state__subtitle">Tente outra busca ou categoria.</span>
    </div>
    <div class="pagination" id="pagination"></div>
  </div>
</section>

@endsection
