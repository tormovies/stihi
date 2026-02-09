@extends('admin.layouts.app')

@section('title', 'Стихи')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Стихи</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Стихи</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.poems.create') }}" class="admin-btn admin-btn-primary">Добавить стих</a>
    </div>
</div>
<form method="GET" class="admin-filter-bar">
    <input type="hidden" name="sort" value="{{ request('sort', 'updated_at') }}">
    <input type="hidden" name="order" value="{{ request('order', 'desc') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Поиск по названию или slug">
    <select name="author_id">
        <option value="">Все авторы</option>
        @foreach($authors as $a)
            <option value="{{ $a->id }}" @selected(request('author_id') == $a->id)>{{ $a->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="admin-btn admin-btn-secondary">Применить</button>
</form>
<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'title', 'label' => 'Название'])</th>
                    <th>Автор</th>
                    <th>Slug</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'likes', 'label' => 'Лайки'])</th>
                    <th>@include('admin.poems.partials.sort-link', ['key' => 'updated_at', 'label' => 'Обновлён'])</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($poems as $poem)
                    <tr>
                        <td>{{ Str::limit($poem->title, 50) }}</td>
                        <td>{{ $poem->author->name }}</td>
                        <td><a href="{{ url('/' . $poem->slug . '/') }}" target="_blank" rel="noopener" class="admin-slug-link"><code>{{ Str::limit($poem->slug, 30) }}</code></a></td>
                        <td>{{ (int) ($poem->likes ?? 0) }}</td>
                        <td>{{ $poem->updated_at?->format('d.m.Y H:i') }}</td>
                        <td class="admin-cell-actions">
                            <a href="{{ route('admin.poems.edit', $poem) }}" class="admin-btn-link">Изменить</a>
                            <form action="{{ route('admin.poems.destroy', $poem) }}" method="POST" class="inline" onsubmit="return confirm('Удалить стих?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn-delete">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $poems->links() }}</div>
</div>
@endsection
