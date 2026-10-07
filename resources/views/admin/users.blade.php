@extends('admin.layout')
@section('title', 'Игроки')
@section('content')
<div class="page-header"><h1>👤 Игроки ({{ $users->total() }})</h1></div>
<div class="search-box">
  <form action="{{ route('admin.users.index') }}" method="GET" style="display:flex;gap:8px;width:100%;">
    <input type="text" name="search" placeholder="Поиск по email..." class="form-input" value="{{ request('search') }}">
    <button type="submit" class="btn btn-secondary btn-sm">🔍</button>
  </form>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>ID</th><th>Email</th><th>Уровень</th><th>Очков</th><th>Монет</th><th>Гемов</th><th>Жизней</th><th>Зарегистрирован</th><th></th></tr></thead>
    <tbody>
      @foreach($users as $user)
      <tr>
        <td>{{ $user->id }}</td>
        <td>{{ $user->email }}</td>
        <td>{{ $user->progress->max_level_reached ?? 0 }}</td>
        <td>{{ number_format($user->progress->total_score ?? 0) }}</td>
        <td>{{ number_format($user->progress->coins ?? 0) }}</td>
        <td>{{ $user->progress->gems ?? 0 }}</td>
        <td>{{ $user->progress->lives ?? 0 }}/5</td>
        <td>{{ $user->created_at->format('d.m.Y') }}</td>
        <td><a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-secondary btn-sm">👁️</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $users->links() }}
</div>
@endsection