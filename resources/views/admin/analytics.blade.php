@extends('admin.layout')
@section('title', 'Аналитика')
@section('content')
<div class="page-header"><h1>📈 Аналитика</h1></div>

<div class="card">
  <div class="card-title">🎮 Сложность уровней (первые 100)</div>
  <table class="table">
    <thead><tr><th>Уровень</th><th>Попыток</th><th>Побед</th><th>Винрейт</th><th>Средний счёт</th><th>Среднее ходов</th></tr></thead>
    <tbody>
      @foreach($levelDifficulty as $l)
      <tr>
        <td>{{ $l['level_id'] }}</td>
        <td>{{ $l['total_attempts'] }}</td>
        <td>{{ $l['wins'] }}</td>
        <td><span class="badge {{ $l['win_rate'] > 50 ? 'badge-success' : ($l['win_rate'] > 30 ? 'badge-warning' : 'badge-danger') }}">{{ $l['win_rate'] }}%</span></td>
        <td>{{ number_format($l['avg_score']) }}</td>
        <td>{{ $l['avg_moves'] }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

<div class="card">
  <div class="card-title">📊 Retention (placeholder)</div>
  <div class="chart-bar">
    @foreach($retention as $r)
      <div class="chart-bar-item" style="height:{{ $r['rate'] }}px;" title="{{ $r['day'] }}: {{ $r['rate'] }}%"></div>
    @endforeach
  </div>
  <div class="chart-labels">
    @foreach($retention as $r)
      <span>{{ $r['day'] }}</span>
    @endforeach
  </div>
</div>

<div class="card">
  <div class="card-title">📡 События (топ 20)</div>
  <table class="table">
    <thead><tr><th>Событие</th><th>Количество</th></tr></thead>
    <tbody>
      @foreach($events as $e)
      <tr><td>{{ $e->event_type }}</td><td>{{ number_format($e->count) }}</td></tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection