# Деплой на сервер (stihotvorenie.su)

Порядок: сначала репозиторий на GitHub и пуш с локальной машины, потом команды на сервере.

---

## Часть 1. Репозиторий (на своей машине)

### 1.1. Создать репозиторий на GitHub

1. Зайти на https://github.com/new  
2. Repository name: `stihi`  
3. Создать **пустой** репозиторий (без README, .gitignore).  
4. Скопировать URL: `https://github.com/tormovies/stihi.git`

### 1.2. Залить проект (локально, в папке проекта)

```powershell
cd C:\projects\port-stihotvorenie
git init
git add -A
git commit -m "Laravel: портал стихов — публичный сайт, админка, поиск, лайки, Яндекс.Метрика"
git branch -M master
git remote add origin https://github.com/tormovies/stihi.git
git push -u origin master
```

Если репо уже есть и коммиты сделаны — только: `git push -u origin master`.

---

## Часть 2. Сервер (SSH)

Пути: домен `/home/a/adminfeg/stihotvorenie.su`, проект в `laravel/`, сайт отдаётся из `laravel/public` (через симлинк).

На сервере используем **PHP 8.3**: Composer вызывается через `php8.3`, все команды Artisan — через `php8.3 artisan`.

### 2.1. Клонировать проект

```bash
cd /home/a/adminfeg/stihotvorenie.su
git clone https://github.com/tormovies/stihi.git laravel
cd laravel
```

### 2.2. Зависимости и .env

```bash
php8.3 $(which composer) install --no-dev --optimize-autoloader
cp .env.example .env
nano .env   # APP_ENV=production, APP_DEBUG=false, DB_*, APP_URL=https://stihotvorenie.su
php8.3 artisan key:generate
```

(Если Composer установлен как `composer.phar` в проекте: `php8.3 composer.phar install ...`.)

### 2.3. Миграции и кэш

```bash
php8.3 artisan migrate --force
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache
php8.3 artisan css:minify
chmod -R 775 storage bootstrap/cache
```

### 2.4. Симлинк public_html → laravel/public

Если старый public_html не нужен (сделайте бэкап при необходимости):

```bash
cd /home/a/adminfeg/stihotvorenie.su
rm -rf public_html
ln -s laravel/public public_html
```

Проверка: открыть https://stihotvorenie.su в браузере.

### 2.5. Кеш для sitemap (админка и сайт должны видеть один кеш)

Если после «Обновить sitemap» в админке дата меняется, но при открытии `/sitemap.xml` карта не отдаётся или выдаёт ошибку — скорее всего запросы админки и сайта пишут/читают кеш по-разному (например, file-кеш и права на `storage/`). В `.env` укажите общий кеш:

```bash
CACHE_STORE=database
```

Таблица для кеша (если ещё не создана):

```bash
php8.3 artisan cache:table
php8.3 artisan migrate --force
```

После смены кеша: «Обновить sitemap» в админке, затем проверить https://stihotvorenie.su/sitemap.xml

**Если дата в админке есть, а sitemap.xml не открывается:** на сервере выполните диагностику в контексте веба и CLI:

1. В `.env` добавьте (временно) строку и выполните `php8.3 artisan config:clear`:
   ```bash
   SITEMAP_DEBUG_KEY=любой_секретный_набор_символов
   ```
2. В админке нажмите «Обновить sitemap».
3. Откройте в браузере (подставьте свой ключ):
   `https://stihotvorenie.su/sitemap-debug?key=любой_секретный_набор_символов`
   Посмотрите, что выводится: значения `_count`, `_1`, `_updated_at`. Если `_1: null` — веб-запрос не видит кеш.
4. Добавьте к URL `&test=1` и обновите страницу — увидите реальную ошибку при генерации ответа (если она есть).
5. Из консоли (SSH) выполните:
   `php8.3 artisan sitemap:status --test-render`
   Сравните: если из CLI всё OK, а из браузера — нет, значит кеш или окружение различаются для веба и CLI.
6. После отладки удалите `SITEMAP_DEBUG_KEY` из `.env` и выполните `php8.3 artisan config:cache`.

---

## Структура на сервере (справка)

- Домен: `/home/a/adminfeg/stihotvorenie.su`
- Проект Laravel: `/home/a/adminfeg/stihotvorenie.su/laravel`
- Текущий сайт (document root): `/home/a/adminfeg/stihotvorenie.su/public_html`

**Важно:** в Laravel точкой входа является папка `public`. В неё не должны попадать `.env`, `app/`, `vendor/` и т.д. Поэтому симлинк делаем на **laravel/public**, а не на laravel целиком.

## Шаги

### 1. Создать папку и загрузить проект

На сервере:

```bash
cd /home/a/adminfeg/stihotvorenie.su
mkdir -p laravel
```

Загрузить в `laravel/` содержимое репозитория (через git clone или заливку файлов):

```bash
cd /home/a/adminfeg/stihotvorenie.su
git clone https://github.com/tormovies/stihi.git laravel
# или: скопировать файлы проекта в laravel/
```

В итоге должно быть:
- `/home/a/adminfeg/stihotvorenie.su/laravel/public/index.php`
- `/home/a/adminfeg/stihotvorenie.su/laravel/artisan`
- `/home/a/adminfeg/stihotvorenie.su/laravel/composer.json`
- и остальные папки Laravel.

### 2. Симлинк document root на Laravel public

Сайт должен отдаваться из **public**-папки Laravel. Два варианта.

**Вариант A: заменить public_html на симлинк**

Если в `public_html` больше ничего не нужно (старый сайт перенесён):

```bash
cd /home/a/adminfeg/stihotvorenie.su
rm -rf public_html   # только если содержимое не нужно!
ln -s laravel/public public_html
```

**Вариант B: сохранить старый public_html и переключить виртуальный хост**

Если панель (cPanel, ISPmanager и т.п.) позволяет задать document root для домена — указать:

```
/home/a/adminfeg/stihotvorenie.su/laravel/public
```

Тогда отдельный симлинк не нужен; старый `public_html` можно оставить как бэкап или удалить позже.

### 3. Настройка Laravel на сервере

Используем PHP 8.3: `php8.3` для Composer и Artisan.

```bash
cd /home/a/adminfeg/stihotvorenie.su/laravel

# зависимости (Composer через php8.3)
php8.3 $(which composer) install --no-dev --optimize-autoloader

# .env из примера
cp .env.example .env
# Отредактировать .env: APP_ENV=production, APP_DEBUG=false, DB_*, и т.д.

php8.3 artisan key:generate
php8.3 artisan migrate --force
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache
```

Права на запись (если веб-сервер не под вашим пользователем — подставьте нужного пользователя/группу):

```bash
chmod -R 775 storage bootstrap/cache
# chown -R user:group storage bootstrap/cache  # при необходимости
```

### 4. Минификация CSS (если не в репозитории)

На проде при `APP_DEBUG=false` подключается `site.min.css`. Если его нет в репо — собрать на сервере:

```bash
php8.3 artisan css:minify
```

---

## Итоговая схема

```
/home/a/adminfeg/stihotvorenie.su/
├── laravel/              # проект (app, public, vendor, .env, ...)
│   ├── public/           # сюда смотрит веб-сервер
│   │   ├── index.php
│   │   ├── css/
│   │   └── ...
│   ├── app/
│   ├── ...
│   └── .env
└── public_html  →  laravel/public   # симлинк (вариант A)
```

Запросы к `https://stihotvorenie.su/` обрабатываются через `laravel/public/index.php`, при этом код и секреты остаются в `laravel/` и не отдаются в веб.
