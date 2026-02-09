# Анализ экспорта WordPress (WordPress.2026-02-09.xml)

## Источник
- **Файл:** `backup-old/WordPress.2026-02-09.xml`
- **Сайт:** https://stihotvorenie.su
- **Формат:** WordPress WXR (eXtended RSS) 1.2

---

## Структура URL на старом сайте (сохраняем)

| Тип | Пример URL | Откуда в XML |
|-----|------------|--------------|
| Главная | `https://stihotvorenie.su/` | Страница «Стихотворения русских поэтов» (slug: `stihotvoreniya-russkih-poetov`) — контент главной с таблицей авторов |
| Автор (поэт) | `https://stihotvorenie.su/pushkin/` | Категория = поэт, `category_nicename` = slug |
| Стихотворение | `https://stihotvorenie.su/v-sevile/` | Пост, `wp:post_name` = slug, `link` в item |
| Страница | `https://stihotvorenie.su/privacy-policy/` | Страница, `wp:post_name` = slug |

**Важно:** В экспорте нет записи с slug `sample-post`. Это дефолтный первый пост WordPress — либо удалён, либо не попал в экспорт. Рекомендация: сделать **301 редирект** `/sample-post/` → `/` для сохранения SEO при наличии внешних ссылок.

---

## Объёмы контента

| Сущность | Количество | Примечание |
|----------|------------|------------|
| **Посты (стихи)** | ~15 496 | `wp:post_type` = post, импортируем только `status` = publish |
| **Страницы** | 2 | 1) Политика конфиденциальности (draft), 2) Стихотворения русских поэтов (главная, publish) |
| **Категории (поэты)** | 41 | Одна служебная «Без рубрики» (slug URL-encoded), остальные — авторы |
| **Вложения** | 1 | 10647.jpg |
| **Пункты меню** | много | Не переносим в первую очередь; меню соберём из списка авторов |

---

## Структура данных в XML

### Категория = автор (поэт)
- `wp:term_id`, `wp:category_nicename` (slug), `wp:cat_name` (имя для отображения)
- Slug автора: латиница, например `agnivcev`, `pushkin`, `cvetaeva`, `cherniy-sasha`.

### Пост = стихотворение
- `title` — название стиха
- `link` — канонический URL (используем для проверки)
- `content:encoded` — текст (часто с HTML: `<p>`, `<br />`)
- `wp:post_id`, `wp:post_name` (slug), `wp:post_date`, `wp:status` (publish/draft)
- `category domain="category" nicename="..."` — одна категория = один автор
- Мета Yoast: `_yoast_wpseo_title`, `_yoast_wpseo_meta_description` — сохраняем для SEO

### Страница
- Аналогично посту: `title`, `content:encoded`, `wp:post_name`, `wp:status`.
- Страница «Стихотворения русских поэтов» содержит в контенте готовую HTML-таблицу со ссылками на `/author-slug/`. Её можно импортировать как контент главной или перегенерировать из списка авторов.

---

## Рекомендации по импорту

1. **Авторы:** импорт из `wp:category` (taxonomy category), кроме «Без рубрики»; slug брать из `category_nicename` (декодировать URL-encoded при необходимости).
2. **Стихи:** только `post_type=post` и `status=publish`; связь с автором по категории (одна категория на пост).
3. **Страницы:** импорт `post_type=page`; для главной использовать страницу со slug `stihotvoreniya-russkih-poetov` или помечать её как «главная» и отдавать на `/`.
4. **SEO:** сохранять `_yoast_wpseo_title` и `_yoast_wpseo_meta_description` в полях meta_title / meta_description.
5. **Контент:** HTML из `content:encoded` сохранять как есть или санитизировать (например, strip опасных тегов).
