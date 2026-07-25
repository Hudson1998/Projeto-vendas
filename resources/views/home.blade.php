@extends('layouts.app')

@section('title', 'HR Moda Feminina')

@section('content')

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
