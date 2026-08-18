@extends('layouts.app')

@section('title', 'HR Moda Online')

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

@endsection
