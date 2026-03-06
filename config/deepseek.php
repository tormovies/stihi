<?php

return [
    /*
    | Шаблон запроса к DeepSeek. Подстановка {{POEMS_JSON}} заменяется на
    | JSON-массив объектов: id, poet, title, poem (первые 100 символов текста).
    | В ответе ожидается JSON с полем items или response — массив объектов
    | с id (или poem_id), meta_title, meta_description, h1, h1_description (или text_by_h1).
    */
    'prompt_template' => <<<'PROMPT'
Ты — SEO-специалист. Получаешь массив объектов с id, poet, title, poem. Для каждого элемента генерируй meta_title (до 60 символов), meta_description (до 160), h1, text_by_h1 (2-3 предложения). Используй обычные кавычки и апострофы в тексте, без HTML-сущностей (без &#039;, &quot; и т.п.). Верни строго JSON: {"items": [{"id": <id>, "meta_title": "...", "meta_description": "...", "h1": "...", "text_by_h1": "..."}, ...]}.

Данные:
{{POEMS_JSON}}
PROMPT,

    /*
    | Шаблон для авторов. Подстановка {{AUTHORS_JSON}} — массив: id, name, years_of_life.
    | Ответ: тот же формат — items с id, meta_title, meta_description, h1, text_by_h1.
    */
    'prompt_template_authors' => <<<'PROMPT'
Ты — SEO-специалист. Получаешь массив авторов (поэтов): у каждого id, name (ФИО), years_of_life (даты жизни). Для каждого генерируй meta_title (до 60 символов), meta_description (до 160), h1, text_by_h1 (1–3 предложения о поэте). Используй обычные кавычки и апострофы, без HTML-сущностей. Верни строго JSON: {"items": [{"id": <id>, "meta_title": "...", "meta_description": "...", "h1": "...", "text_by_h1": "..."}, ...]}.

Данные:
{{AUTHORS_JSON}}
PROMPT,
];
