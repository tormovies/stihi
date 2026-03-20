<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UrlRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UrlRedirectController extends Controller
{
    private const PATH_REGEX = '/^[a-z0-9\-]+(?:\/[a-z0-9\-]+)*$/u';

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $query = UrlRedirect::query()->orderBy('from_path');
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('from_path', 'like', $like)
                    ->orWhere('to_path', 'like', $like);
            });
        }
        $redirects = $query->paginate(30)->withQueryString();

        return view('admin.seo.redirects.index', compact('redirects', 'q'));
    }

    public function create(): View
    {
        return view('admin.seo.redirects.form', ['redirect' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        UrlRedirect::create($data);
        UrlRedirect::forgetMapCache();

        return redirect()->route('admin.seo.redirects.index')->with('success', 'Редирект 301 добавлен.');
    }

    public function edit(UrlRedirect $redirect): View
    {
        return view('admin.seo.redirects.form', compact('redirect'));
    }

    public function update(Request $request, UrlRedirect $redirect): RedirectResponse
    {
        $data = $this->validated($request, $redirect->id);
        $redirect->update($data);
        UrlRedirect::forgetMapCache();

        return redirect()->route('admin.seo.redirects.index')->with('success', 'Редирект сохранён.');
    }

    public function destroy(UrlRedirect $redirect): RedirectResponse
    {
        $redirect->delete();
        UrlRedirect::forgetMapCache();

        return redirect()->route('admin.seo.redirects.index')->with('success', 'Редирект удалён.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $regexRule = 'regex:' . self::PATH_REGEX;
        $rules = [
            'from_path' => [
                'required',
                'string',
                'max:255',
                $regexRule,
                Rule::unique('url_redirects', 'from_path')->ignore($ignoreId),
            ],
            'to_path' => [
                'required',
                'string',
                'max:255',
                $regexRule,
            ],
        ];
        $messages = [
            'from_path.regex' => 'Только латиница в нижнем регистре, цифры, дефис и слэши между сегментами (например: stih или stih/analiz).',
            'to_path.regex' => 'То же для пути назначения.',
        ];
        $request->validate($rules, $messages);

        $rawFrom = (string) $request->input('from_path');
        $rawTo = (string) $request->input('to_path');
        if (stripos($rawFrom, 'http') !== false || stripos($rawTo, 'http') !== false) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'from_path' => 'Внешние URL недопустимы, только внутренний путь.',
            ]);
        }

        $from = UrlRedirect::normalizeForStorage($rawFrom);
        $to = UrlRedirect::normalizeForStorage($rawTo);

        if ($from === $to) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'to_path' => 'Путь «куда» не должен совпадать с путём «откуда».',
            ]);
        }

        $this->assertReservedSegments($from, 'from_path');
        $this->assertReservedSegments($to, 'to_path');

        return ['from_path' => $from, 'to_path' => $to];
    }

    private function assertReservedSegments(string $path, string $field): void
    {
        $first = explode('/', $path)[0] ?? '';
        $reserved = ['admin', 'poem', 'search', 'tegi', 'favorites', 'ponravivshiesya-vsem', 'robots.txt', 'sitemap.xml', 'sitemap-debug', 'up'];
        if (in_array($first, $reserved, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => 'Первый сегмент пути зарезервирован системой.',
            ]);
        }
    }
}
