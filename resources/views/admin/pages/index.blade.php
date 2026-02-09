@extends('admin.layouts.app')

@section('title', 'Страницы')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><span>Страницы</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Страницы</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.pages.create') }}" class="admin-btn admin-btn-primary">Добавить страницу</a>
    </div>
</div>
<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Slug</th>
                    <th>Название</th>
                    <th>Главная</th>
                    <th>Опубликована</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($pages as $page)
                    <tr>
                        <td><a href="{{ url('/' . $page->slug . '/') }}" target="_blank" rel="noopener" class="admin-slug-link"><code>{{ $page->slug }}</code></a></td>
                        <td>{{ $page->title }}</td>
                        <td>{{ $page->is_home ? 'Да' : '—' }}</td>
                        <td>{{ $page->is_published ? 'Да' : 'Нет' }}</td>
                        <td class="admin-cell-actions">
                            <a href="{{ route('admin.pages.edit', $page) }}" class="admin-btn-link">Изменить</a>
                            <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="inline" onsubmit="return confirm('Удалить страницу?');">
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
    <div class="pagination-wrap">{{ $pages->links() }}</div>
</div>
@endsection
