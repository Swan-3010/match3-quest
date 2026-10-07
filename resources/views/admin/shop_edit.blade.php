@extends('admin.layout')
@section('title', 'Редактирование товара')
@section('content')
<div class="page-header"><h1>✏️ {{ $item->name }}</h1></div>
<div class="card">
  <form action="{{ route('admin.shop.update', $item->id) }}" method="POST">
    @csrf @method('PUT')
    <div class="form-group"><label class="form-label">Название</label><input type="text" name="name" class="form-input" value="{{ $item->name }}"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group"><label class="form-label">Цена (монеты)</label><input type="number" name="price_coins" class="form-input" value="{{ $item->price_coins }}"></div>
      <div class="form-group"><label class="form-label">Цена (гемы)</label><input type="number" name="price_gems" class="form-input" value="{{ $item->price_gems }}"></div>
    </div>
    <div class="form-group"><label class="form-label">Сортировка</label><input type="number" name="sort_order" class="form-input" value="{{ $item->sort_order }}"></div>
    <div class="form-group">
      <label class="form-label">Активен</label>
      <select name="is_active" class="form-input">
        <option value="1" {{ $item->is_active ? 'selected' : '' }}>Да</option>
        <option value="0" {{ !$item->is_active ? 'selected' : '' }}>Нет</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">💾 Сохранить</button>
  </form>
</div>
@endsection