@extends('admin.layouts.app')

@section('title', 'Результат оптимизации DeepSeek')

@section('breadcrumb')
    <a href="{{ route('admin.dashboard') }}">Админка</a><span>›</span><a href="{{ route('admin.deepseek.index') }}">Оптимизация SEO</a><span>›</span><span>Результат</span>
@endsection

@section('content')
<div class="admin-toolbar">
    <h1>Результат обработки</h1>
</div>
<div class="admin-card">
    @if($error)
        <p class="alert alert-danger">{{ $error }}</p>
    @endif
    @if(isset($message) && $message)
        <p class="alert alert-success">{{ $message }}</p>
    @endif
    <p><strong>Обработано {{ $entity_label ?? 'стихов' }}:</strong> {{ $processed }}</p>
    @if(!empty($failed))
        <p><strong>Не удалось обновить (id):</strong> {{ implode(', ', $failed) }}</p>
    @endif
    @if(!empty($rawResponse))
        <details class="admin-deepseek-raw-response" style="margin-top: 1rem;">
            <summary>Ответ DeepSeek (сырой JSON)</summary>
            <pre class="admin-deepseek-raw-pre">{{ e($rawResponse) }}</pre>
        </details>
    @endif
    <p style="margin-top: 1rem;"><a href="{{ route('admin.deepseek.index') }}" class="admin-btn admin-btn-secondary">← Назад к настройкам</a></p>
</div>
@endsection
