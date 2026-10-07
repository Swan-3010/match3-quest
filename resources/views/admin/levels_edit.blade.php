@extends('admin.layout')
@section('title', 'Редактирование уровня ' . $level->level_number)
@section('content')
<div class="page-header"><h1>✏️ Уровень {{ $level->level_number }}</h1></div>
<div class="card">
  <form action="{{ route('admin.levels.update', $level->id) }}" method="POST">
    @csrf @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="form-group">
        <label class="form-label">Лимит ходов</label>
        <input type="number" name="moves_limit" class="form-input" value="{{ $level->moves_limit }}">
      </div>
      <div class="form-group">
        <label class="form-label">Целевой счёт</label>
        <input type="number" name="target_score" class="form-input" value="{{ $level->target_score }}">
      </div>
      <div class="form-group">
        <label class="form-label">⭐2 порог</label>
        <input type="number" name="star_2_score" class="form-input" value="{{ $level->star_2_score }}">
      </div>
      <div class="form-group">
        <label class="form-label">⭐3 порог</label>
        <input type="number" name="star_3_score" class="form-input" value="{{ $level->star_3_score }}">
      </div>
      <div class="form-group">
        <label class="form-label">Количество цветов</label>
        <input type="number" name="colors_count" class="form-input" value="{{ $level->colors_count }}" min="3" max="8">
      </div>
      <div class="form-group">
        <label class="form-label">Активен</label>
        <select name="is_active" class="form-input">
          <option value="1" {{ $level->is_active ? 'selected' : '' }}>Да</option>
          <option value="0" {{ !$level->is_active ? 'selected' : '' }}>Нет</option>
        </select>
      </div>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top:16px;">💾 Сохранить</button>
    <a href="{{ route('admin.levels.index') }}" class="btn btn-secondary" style="margin-top:16px;">Отмена</a>
  </form>
</div>
@endsection