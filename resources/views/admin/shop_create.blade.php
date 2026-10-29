@extends('admin.layout')
@section('title', 'Новый товар')
@section('content')
<div class="page-header"><h1>➕ Новый товар</h1></div>
<div class="card">
  <form action="{{ route('admin.shop.store') }}" method="POST">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group"><label class="form-label">Код</label><input type="text" name="code" class="form-input" value="{{ old('code') }}"></div>
      <div class="form-group"><label class="form-label">Название</label><input type="text" name="name" class="form-input" value="{{ old('name') }}"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group">
        <label class="form-label">Категория</label>
        <select name="category" class="form-input">
          @foreach(['imba', 'boost', 'life', 'gems'] as $c)
          <option value="{{ $c }}" {{ old('category') === $c ? 'selected' : '' }}>{{ $c }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group"><label class="form-label">Контекст</label><input type="text" name="context" class="form-input" value="{{ old('context', 'general') }}"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
      <div class="form-group"><label class="form-label">Цена (монеты)</label><input type="number" name="price_coins" class="form-input" value="{{ old('price_coins') }}"></div>
      <div class="form-group"><label class="form-label">Цена (гемы)</label><input type="number" name="price_gems" class="form-input" value="{{ old('price_gems') }}"></div>
      <div class="form-group">
        <label class="form-label">Валюта цены</label>
        <select name="price_currency" class="form-input">
          <option value="coins" {{ old('price_currency') === 'coins' ? 'selected' : '' }}>coins</option>
          <option value="gems" {{ old('price_currency') === 'gems' ? 'selected' : '' }}>gems</option>
        </select>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group"><label class="form-label">Сортировка</label><input type="number" name="sort_order" class="form-input" value="{{ old('sort_order', 0) }}"></div>
      <div class="form-group">
        <label class="form-label">Активен</label>
        <select name="is_active" class="form-input">
          <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Да</option>
          <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Нет</option>
        </select>
      </div>
    </div>
    <div class="form-group"><label class="form-label">Эффект (JSON)</label><textarea name="effect_json" class="form-input" rows="3">{{ old('effect_json') }}</textarea></div>
    <button type="submit" class="btn btn-primary">💾 Создать</button>
  </form>
</div>
@endsection
