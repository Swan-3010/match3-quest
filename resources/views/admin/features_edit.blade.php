@extends('admin.layout')
@section('title', 'Редактирование фишки')
@section('content')
<div class="page-header"><h1>✏️ {{ $feature->name }}</h1></div>
<div class="card">
  <form action="{{ route('admin.features.update', $feature->id) }}" method="POST">
    @csrf @method('PUT')
    <div class="form-group"><label class="form-label">Уровень разблокировки</label><input type="number" name="unlock_level" class="form-input" value="{{ $feature->unlock_level }}"></div>
    <div class="form-group"><label class="form-label">Название</label><input type="text" name="name" class="form-input" value="{{ $feature->name }}"></div>
    <div class="form-group"><label class="form-label">Условие получения</label><textarea name="unlock_condition" class="form-input" rows="3">{{ $feature->unlock_condition }}</textarea></div>
    <div class="form-group">
      <label class="form-label">Активна</label>
      <select name="is_active" class="form-input">
        <option value="1" {{ $feature->is_active ? 'selected' : '' }}>Да</option>
        <option value="0" {{ !$feature->is_active ? 'selected' : '' }}>Нет</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">💾 Сохранить</button>
  </form>
</div>
@endsection