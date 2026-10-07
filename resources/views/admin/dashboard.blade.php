@extends('admin.layout')

@section('title', 'Дашборд')

@section('content')
<div class="page-header">
  <h1>📊 Дашборд</h1>
  <span style="color:var(--text-3);font-size:14px;">{{ now()->format('d.m.Y H:i') }}</span>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="value">{{ $stats['total_users'] }}</div>
    <div class="label">Всего игроков</div>
  </div>
  <div class="stat-card">
    <div class="value">{{ $stats['active_today'] }}</div>
    <div class="label">Активны сегодня</div>
  </div>
  <div class="stat-card">
    <div class="value">{{ round($stats['win_rate'], 1) }}%</div>
    <div class="label">Винрейт</div>
  </div>
  <div class="stat-card">
    <div class="value">{{ number_format($stats['total_revenue_coins']) }}</div>
    <div class="label">Монет потрачено</div>
  </div>
  <div class="stat-card">
    <div class="value">{{ $stats['levels_count'] }}</div>
    <div class="label">Уровней в БД</div>
  </div>
  <div class="stat-card">
    <div class="value">{{ $stats['total_sessions'] }}</div>
    <div class="label">Игровых сессий</div>
  </div>
</div>

<div class="card">
  <div class="card-title">📈 DAU (последние 7 дней)</div>
  <div class="chart-bar">
    @foreach($dau as $d)
      <div class="chart-bar-item" style="height:{{ min(100, max(4, $d['users'] * 2)) }}px;" title="{{ $d['date'] }}: {{ $d['users'] }}"></div>
    @endforeach
  </div>
  <div class="chart-labels">
    @foreach($dau as $d)
      <span>{{ substr($d['date'], 5) }}</span>
    @endforeach
  </div>
</div>

<div class="card">
  <div class="card-title">🔥 Топ уровней по попыткам</div>
  <table class="table">
    <thead><tr><th>Уровень</th><th>Попыток</th><th>Средний счёт</th></tr></thead>
    <tbody>
      @foreach($topLevels as $l)
      <tr>
        <td>Уровень {{ $l->level_id }}</td>
        <td>{{ $l->attempts }}</td>
        <td>{{ round($l->avg_score) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection