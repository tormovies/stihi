@extends('admin.layouts.app')

@section('title', 'Теги')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Теги</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Теги</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.tags.create') }}" class="admin-btn admin-btn-primary">Добавить тег</a>
        <a href="{{ route('admin.tags.bulk') }}" class="admin-btn admin-btn-secondary">Массовое добавление</a>
        <a href="{{ route('admin.deepseek.index') }}" class="admin-btn admin-btn-secondary">DeepSeek (SEO и разметка)</a>
    </div>
</div>
<form method="get" action="{{ route('admin.tags.index') }}" class="admin-filter-bar">
    <label for="tags-q" class="admin-filter-label">Поиск по названию, slug, SEO</label>
    <input type="search" id="tags-q" name="q" value="{{ old('q', $q ?? '') }}" placeholder="название, slug…">
    <input type="hidden" name="sort" value="{{ $sort ?? 'sort_order' }}">
    <input type="hidden" name="order" value="{{ $order ?? 'asc' }}">
    <button type="submit" class="admin-btn admin-btn-secondary">Искать</button>
</form>
<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                @php
                    $currentSort = $sort ?? 'sort_order';
                    $currentOrder = $order ?? 'asc';
                    $queryParams = ['q' => $q ?? ''];
                    $thSort = function ($column, $label) use ($currentSort, $currentOrder, $queryParams) {
                        $isActive = ($currentSort === $column);
                        $nextOrder = ($isActive && $currentOrder === 'asc') ? 'desc' : 'asc';
                        if ($column === 'updated_at') {
                            $nextOrder = ($isActive && $currentOrder === 'desc') ? 'asc' : 'desc';
                        }
                        if ($column === 'poems_count') {
                            $nextOrder = ($isActive && $currentOrder === 'desc') ? 'asc' : 'desc';
                        }
                        $url = route('admin.tags.index', array_merge($queryParams, ['sort' => $column, 'order' => $nextOrder]));
                        $arrow = $isActive ? ($currentOrder === 'asc' ? ' ↑' : ' ↓') : '';
                        return '<a href="' . e($url) . '" class="admin-th-sort">' . e($label) . $arrow . '</a>';
                    };
                @endphp
                <th>{!! $thSort('name', 'Название') !!}</th>
                <th>{!! $thSort('slug', 'Slug') !!}</th>
                <th>{!! $thSort('poems_count', 'Стихов') !!}</th>
                <th>{!! $thSort('sort_order', 'Порядок') !!}</th>
                <th>{!! $thSort('updated_at', 'Обновлён') !!}</th>
                <th></th>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                    <tr>
                        <td>{{ $tag->name }}</td>
                        <td><a href="{{ url('/tegi/' . $tag->slug . '/') }}" target="_blank" rel="noopener" class="admin-slug-link"><code>{{ $tag->slug }}</code></a></td>
                        <td>{{ $tag->poems_count ?? 0 }}</td>
                        <td>{{ $tag->sort_order }}</td>
                        <td>{{ $tag->updated_at?->format('d.m.Y H:i') }}</td>
                        <td class="admin-cell-actions">
                            <a href="{{ route('admin.tags.edit', $tag) }}" class="admin-btn-link">Изменить</a>
                            <form action="{{ route('admin.tags.destroy', $tag) }}" method="POST" class="inline" onsubmit="return confirm('Удалить тег «{{ addslashes($tag->name) }}»?');">
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
    <div class="pagination-wrap">{{ $tags->links() }}</div>
</div>
@endsection
