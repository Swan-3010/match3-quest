@extends('admin.layout')
@section('title', 'Конфиги')
@section('content')
<div class="page-header"><h1>⚙️ Конфиги</h1></div>
<div class="card">
  <table class="table">
    <thead><tr><th>Ключ</th><th>Версия</th><th>Обновлён</th><th></th></tr></thead>
    <tbody>
      @foreach($configs as $config)
      <tr>
        <td><code>{{ $config->config_key }}</code></td>
        <td>{{ $config->version }}</td>
        <td>{{ $config->updated_at }}</td>
        <td><a href="{{ route('admin.configs.edit', $config->id) }}" class="btn btn-secondary btn-sm">✏️</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $configs->links() }}
</div>
@endsection
