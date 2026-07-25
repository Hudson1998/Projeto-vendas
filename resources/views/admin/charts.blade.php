@extends('layouts.admin')

@section('title', 'Gráficos · Painel HR')

@section('content')
<div class="admin-wrap">
  <div class="admin-page-title">
    <h1>Gráficos</h1>
    <span class="admin-live-indicator"><span class="admin-live-dot"></span> Atualizando automaticamente</span>
  </div>

  <app-root></app-root>
</div>

<link rel="stylesheet" href="{{ asset('admin-charts/browser/styles.css') }}">
<script src="{{ asset('admin-charts/browser/main.js') }}" type="module"></script>
@endsection
