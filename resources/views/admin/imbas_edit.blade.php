@extends('admin.layout')
@section('title', 'Редактирование имбы')
@section('content')
<div class="page-header"><h1>✏️ {{ $imba->name }}</h1></div>
<div class="card">
  <form action="{{ route('admin.imbas.update', $imba->id) }}" method="POST">
    @csrf @method('PUT')
    <div class="form-group"><label class="form-label">Название</label><input type="text" name="name" class="form-input" value="{{ $imba->name }}"></div>
    <div class="form-group"><label class="form-label">Цена (монеты)</label><input type="number" name="base_price_coins" class="form-input" value="{{ $imba->base_price_coins }}"></div>
    <div class="form-group"><label class="form-label">Цена (гемы)</label><input type="number" name="base_price_gems" class="form-input" value="{{ $imba->base_price_gems }}"></div>
    <div class="form-group">
      <label class="form-label">Активна</label>
      <select name="is_active" class="form-input">
        <option value="1" {{ $imba->is_active ? 'selected' : '' }}>Да</option>
        <option value="0" {{ !$imba->is_active ? 'selected' : '' }}>Нет</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">💾 Сохранить</button>
  </form>
</div>
@endsection