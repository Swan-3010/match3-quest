@extends('admin.layout')
@section('title', 'Фишки')
@section('content')
<div class="page-header"><h1>⭐ Фишки</h1></div>
<div class="card">
  <table class="table">
    <thead><tr><th>Уровень</th><th>Код</th><th>Название</th><th>Условие</th><th>Статус</th><th></th></tr></thead>
    <tbody>
      @foreach($features as $f)
      <tr>
        <td>{{ $f->unlock_level }}</td>
        <td><code>{{ $f->code }}</code></td>
        <td>{{ $f->name }}</td>
        <td>{{ Str::limit($f->unlock_condition, 40) }}</td>
        <td><span class="badge {{ $f->is_active ? 'badge-success' : 'badge-danger' }}">{{ $f->is_active ? 'Активна' : 'Выключена' }}</span></td>
        <td><a href="{{ route('admin.features.edit', $f->id) }}" class="btn btn-secondary btn-sm">✏️</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $features->links() }}
</div>
@endsection