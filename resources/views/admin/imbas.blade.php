@extends('admin.layout')
@section('title', 'Имбы')
@section('content')
<div class="page-header"><h1>🔨 Имбы</h1></div>
<div class="card">
  <table class="table">
    <thead><tr><th>Код</th><th>Название</th><th>Цена (монеты)</th><th>Цена (гемы)</th><th>Статус</th><th></th></tr></thead>
    <tbody>
      @foreach($imbas as $i)
      <tr>
        <td><code>{{ $i->code }}</code></td>
        <td>{{ $i->name }}</td>
        <td>{{ $i->base_price_coins ?: '-' }}</td>
        <td>{{ $i->base_price_gems ?: '-' }}</td>
        <td><span class="badge {{ $i->is_active ? 'badge-success' : 'badge-danger' }}">{{ $i->is_active ? 'Активна' : 'Выключена' }}</span></td>
        <td><a href="{{ route('admin.imbas.edit', $i->id) }}" class="btn btn-secondary btn-sm">✏️</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $imbas->links() }}
</div>
@endsection