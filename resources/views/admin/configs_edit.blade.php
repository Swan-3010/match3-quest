@extends('admin.layout')
@section('title', 'Редактирование конфига')
@section('content')
<div class="page-header"><h1>⚙️ {{ $config->config_key }} <span class="badge badge-warning">v{{ $config->version }}</span></h1></div>
<div class="card">
  <form action="{{ route('admin.configs.update', $config->id) }}" method="POST">
    @csrf @method('PUT')
    <div class="form-group">
      <label class="form-label">Значение (JSON)</label>
      <textarea name="config_value" class="form-input" rows="16">{{ json_encode($config->config_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
      @error('config_value')<div style="color:var(--danger);font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary">💾 Сохранить (версия +1)</button>
  </form>
</div>
@endsection
