@extends('admin.layout')
@section('title', 'Магазин')
@section('content')
<div class="page-header"><h1>🛒 Магазин</h1></div>
<div class="card">
  <table class="table">
    <thead><tr><th>Код</th><th>Название</th><th>Категория</th><th>Цена</th><th>Контекст</th><th>Сортировка</th><th></th></tr></thead>
    <tbody>
      @foreach($items as $item)
      <tr>
        <td><code>{{ $item->code }}</code></td>
        <td>{{ $item->name }}</td>
        <td><span class="badge badge-warning">{{ $item->category }}</span></td>
        <td>{{ $item->price_coins ? '🪙 '.$item->price_coins : ($item->price_gems ? '💎 '.$item->price_gems : 'Бесплатно') }}</td>
        <td>{{ $item->context }}</td>
        <td>{{ $item->sort_order }}</td>
        <td><a href="{{ route('admin.shop.edit', $item->id) }}" class="btn btn-secondary btn-sm">✏️</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $items->links() }}
</div>
@endsection