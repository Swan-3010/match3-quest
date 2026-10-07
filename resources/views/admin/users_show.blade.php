@extends('admin.layout')
@section('title', 'Игрок #' . $user->id)
@section('content')
<div class="page-header"><h1>👤 {{ $user->email }}</h1></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <div class="card">
    <div class="card-title">📊 Прогресс</div>
    <p>Макс. уровень: <strong>{{ $user->progress->max_level_reached ?? 0 }}</strong></p>
    <p>Всего очков: <strong>{{ number_format($user->progress->total_score ?? 0) }}</strong></p>
    <p>Звёзд: <strong>{{ $user->progress->total_stars ?? 0 }}</strong></p>
    <p>Уровень игрока: <strong>{{ $user->progress->player_level ?? 1 }}</strong></p>
    <p>XP: <strong>{{ $user->progress->current_xp ?? 0 }} / {{ $user->progress->xp_for_next_level ?? 5000 }}</strong></p>
  </div>
  <div class="card">
    <div class="card-title">💰 Ресурсы</div>
    <p>Монеты: <strong>{{ number_format($user->progress->coins ?? 0) }}</strong></p>
    <p>Гемы: <strong>{{ $user->progress->gems ?? 0 }}</strong></p>
    <p>Жизни: <strong>{{ $user->progress->lives ?? 0 }}/5</strong></p>
    <p>Серия входов: <strong>{{ $user->progress->login_streak ?? 0 }}</strong></p>
  </div>
</div>
<div class="card">
  <div class="card-title">🎒 Инвентарь</div>
  <table class="table">
    <thead><tr><th>Имба</th><th>Количество</th></tr></thead>
    <tbody>
      @forelse($user->inventory as $inv)
      <tr><td>{{ $inv->imba_code }}</td><td>{{ $inv->quantity }}</td></tr>
      @empty
      <tr><td colspan="2" style="color:var(--text-3);">Инвентарь пуст</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="card">
  <div class="card-title">🎯 Сессии (последние 10)</div>
  <table class="table">
    <thead><tr><th>Уровень</th><th>Результат</th><th>Счёт</th><th>Ходов</th><th>Дата</th></tr></thead>
    <tbody>
      @foreach($user->sessions->take(10) as $s)
      <tr>
        <td>{{ $s->level_id }}</td>
        <td><span class="badge {{ $s->result === 'win' ? 'badge-success' : 'badge-danger' }}">{{ $s->result }}</span></td>
        <td>{{ number_format($s->score) }}</td>
        <td>{{ $s->moves_used }}</td>
        <td>{{ $s->created_at->format('d.m.Y H:i') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection