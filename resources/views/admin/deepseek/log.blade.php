@extends('admin.layouts.app')

@section('title', 'Лог DeepSeek')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.deepseek.index') }}">Оптимизация SEO</a><span>›</span><span>Лог</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Лог запросов и результатов DeepSeek</h1>
    <div class="admin-actions">
        <a href="{{ route('admin.deepseek.index') }}" class="admin-btn admin-btn-secondary">← Настройки</a>
    </div>
</div>
<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th>Обработано</th>
                    <th>Ошибки (id)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->created_at?->format('d.m.Y H:i:s') }}</td>
                        <td>@if(($log->entity_type ?? 'poem') === 'author')авторы@elseif(($log->entity_type ?? '') === 'analysis')анализы@elseif(($log->entity_type ?? '') === 'tag')теги (SEO)@elseif(($log->entity_type ?? '') === 'poem_tag')разметка по тегам@elseстихи@endif</td>
                        <td>
                            @if($log->status === 'success')
                                <span class="admin-log-status admin-log-status--success">успех</span>
                            @elseif($log->status === 'api_error')
                                <span class="admin-log-status admin-log-status--error">ошибка API</span>
                            @elseif($log->status === 'parse_error')
                                <span class="admin-log-status admin-log-status--warn">ошибка разбора</span>
                            @else
                                {{ $log->status }}
                            @endif
                        </td>
                        <td>{{ $log->processed_count }}</td>
                        <td>{{ $log->failed_ids ? implode(', ', $log->failed_ids) : '—' }}</td>
                    </tr>
                    @if($log->error_message)
                        <tr class="admin-log-detail-row">
                            <td colspan="6"><strong>Сообщение:</strong> {{ e($log->error_message) }}</td>
                        </tr>
                    @endif
                    @if(isset($log->updated_poems) && $log->updated_poems->isNotEmpty())
                        <tr class="admin-log-detail-row">
                            <td colspan="6">
                                <strong>Обновлённые страницы {{ !empty($log->is_analysis) ? '(анализы)' : '(стихи)' }}:</strong>
                                <ul class="admin-log-links">
                                    @foreach($log->updated_poems as $poem)
                                        <li>
                                            <a href="{{ url('/' . $poem->slug . '/') }}" target="_blank" rel="noopener">Стих: {{ e($poem->title) }}</a>
                                            @if(!empty($log->is_analysis))
                                                — <a href="{{ url('/' . $poem->slug . '/analiz/') }}" target="_blank" rel="noopener">Анализ</a>
                                            @endif
                                            @if($poem->author)
                                                — <a href="{{ url('/' . $poem->author->slug . '/') }}" target="_blank" rel="noopener">автор: {{ e($poem->author->name) }}</a>
                                            @endif
                                            <a href="{{ route('admin.poems.edit', $poem) }}" class="admin-log-edit-link" title="Изменить в админке">✎</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif
                    @if(isset($log->updated_authors) && $log->updated_authors->isNotEmpty())
                        <tr class="admin-log-detail-row">
                            <td colspan="6">
                                <strong>Обновлённые страницы (авторы):</strong>
                                <ul class="admin-log-links">
                                    @foreach($log->updated_authors as $author)
                                        <li>
                                            <a href="{{ url('/' . $author->slug . '/') }}" target="_blank" rel="noopener">Автор: {{ e($author->name) }}</a>
                                            <a href="{{ route('admin.authors.edit', $author) }}" class="admin-log-edit-link" title="Изменить в админке">✎</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif
                    @if(isset($log->updated_tags) && $log->updated_tags->isNotEmpty())
                        <tr class="admin-log-detail-row">
                            <td colspan="6">
                                <strong>Обновлённые теги (SEO):</strong>
                                <ul class="admin-log-links">
                                    @foreach($log->updated_tags as $tag)
                                        <li>
                                            {{ e($tag->name) }} <code>{{ e($tag->slug) }}</code>
                                            <a href="{{ route('admin.tags.edit', $tag) }}" class="admin-log-edit-link" title="Изменить в админке">✎</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif
                    <tr class="admin-log-detail-row">
                        <td colspan="6">
                            <details class="admin-log-details">
                                <summary>Тело запроса в API (как отправляется в DeepSeek)</summary>
                                @php
                                    $reqText = $log->request_full ?? ($log->request_payload ? json_encode($log->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—');
                                    $reqText = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], (string) $reqText);
                                @endphp
                                <pre class="admin-deepseek-raw-pre">{{ $reqText }}</pre>
                            </details>
                            <details class="admin-log-details">
                                <summary>Ответ (сырой, как пришёл от API)</summary>
                                @php
                                    $respText = (string) ($log->response_raw ?? '—');
                                    $respText = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $respText);
                                @endphp
                                <pre class="admin-deepseek-raw-pre">{{ $respText }}</pre>
                            </details>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $logs->links() }}</div>
</div>
@endsection
