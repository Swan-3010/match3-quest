@extends('admin.layout')
@section('title', 'Уровни')
@section('content')
<div class="page-header">
  <h1>🎮 Уровни ({{ $levels->total() }})</h1>
  <div style="display:flex;gap:8px;">
    <form action="{{ route('admin.levels.import') }}" method="POST" enctype="multipart/form-data" style="display:flex;gap:8px;">
      @csrf
      <input type="file" name="csv" accept=".csv" class="form-input" style="width:auto;">
      <button type="submit" class="btn btn-secondary btn-sm">📥 Импорт CSV</button>
    </form>
    <form action="{{ route('admin.levels.generate') }}" method="POST" style="display:inline;">
      @csrf
      <input type="hidden" name="count" value="500">
      <button type="submit" class="btn btn-primary btn-sm">⚡ Сгенерировать 500</button>
    </form>
  </div>
</div>

<div class="search-box">
  <form action="{{ route('admin.levels.index') }}" method="GET" style="display:flex;gap:8px;width:100%;">
    <input type="text" name="search" placeholder="Поиск по номеру..." class="form-input" value="{{ request('search') }}">
    <button type="submit" class="btn btn-secondary btn-sm">🔍</button>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead>
      <tr><th>№</th><th>Ходы</th><th>Цель</th><th>⭐2</th><th>⭐3</th><th>Цветов</th><th>Статус</th><th></th></tr>
    </thead>
    <tbody>
      @foreach($levels as $level)
      <tr>
        <td><strong>{{ $level->level_number }}</strong></td>
        <td>{{ $level->moves_limit }}</td>
        <td>{{ number_format($level->target_score) }}</td>
        <td>{{ number_format($level->star_2_score) }}</td>
        <td>{{ number_format($level->star_3_score) }}</td>
        <td>{{ $level->colors_count }}</td>
        <td><span class="badge {{ $level->is_active ? 'badge-success' : 'badge-danger' }}">{{ $level->is_active ? 'Активен' : 'Выключен' }}</span></td>
        <td><a href="{{ route('admin.levels.edit', $level->id) }}" class="btn btn-secondary btn-sm">✏️</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $levels->links() }}
</div>
@endsection