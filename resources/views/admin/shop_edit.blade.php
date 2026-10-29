@extends('admin.layout')
@section('title', 'Редактирование товара')
@section('content')
<div class="page-header"><h1>✏️ {{ $item->name }}</h1></div>
<div class="card">
  <form action="{{ route('admin.shop.update', $item->id) }}" method="POST">
    @csrf @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group"><label class="form-label">Код</label><input type="text" name="code" class="form-input" value="{{ $item->code }}"></div>
      <div class="form-group"><label class="form-label">Название</label><input type="text" name="name" class="form-input" value="{{ $item->name }}"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group">
        <label class="form-label">Категория</label>
        <select name="category" class="form-input">
          @foreach(['imba', 'boost', 'life', 'gems'] as $c)
          <option value="{{ $c }}" {{ $item->category === $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group"><label class="form-label">Контекст</label><input type="text" name="context" class="form-input" value="{{ $item->context }}"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
      <div class="form-group"><label class="form-label">Цена (монеты)</label><input type="number" name="price_coins" class="form-input" value="{{ $item->price_coins }}"></div>
      <div class="form-group"><label class="form-label">Цена (гемы)</label><input type="number" name="price_gems" class="form-input" value="{{ $item->price_gems }}"></div>
      <div class="form-group">
        <label class="form-label">Валюта цены</label>
        <select name="price_currency" class="form-input">
          <option value="coins" {{ ($item->price_currency ?? 'coins') === 'coins' ? 'selected' : '' }}>coins</option>
          <option value="gems" {{ $item->price_currency === 'gems' ? 'selected' : '' }}>gems</option>
        </select>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group"><label class="form-label">Сортировка</label><input type="number" name="sort_order" class="form-input" value="{{ $item->sort_order }}"></div>
      <div class="form-group">
        <label class="form-label">Активен</label>
        <select name="is_active" class="form-input">
          <option value="1" {{ $item->is_active ? 'selected' : '' }}>Да</option>
          <option value="0" {{ !$item->is_active ? 'selected' : '' }}>Нет</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label class="form-label">Эффект (JSON)</label><textarea name="effect_json" class="form-input" rows="3">{{ is_array($item->effect_json) ? json_encode($item->effect_json, JSON_UNESCAPED_UNICODE) : $item->effect_json }}</textarea></div>
    <button type="submit" class="btn btn-primary">💾 Сохранить</button>
  </form>
</div>
@endsection
