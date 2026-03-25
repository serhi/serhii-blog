# serhii.blog

Репозиторій WordPress-блогу [serhii.blog](https://serhii.blog).

## Що відслідковується в git

Репозиторій **не містить WordPress-ядро** — тільки кастомні файли:

- `wp-content/themes/pulitzer/` — активна тема
- `wp-content/plugins/` — встановлені плагіни

WordPress core (`wp-admin/`, `wp-includes/`, `wp-*.php`), `wp-config.php`, `uploads/` та `deploy.sh` — у `.gitignore`.

## Локальне середовище

Сайт запускається через [Local by Flywheel](https://localwp.com/).

1. Клонувати репо в `~/Local Sites/serhii-blog/app/public/`
2. Запустити сайт у Local
3. Відкрити `http://serhii-blog.local`

## Деплой на продакшн

Деплой виконується через `deploy.sh` (rsync) — файл не у git, зберігається локально.

```bash
bash deploy.sh
```

Синхронізує `wp-content/themes/` і `wp-content/plugins/` на сервер через SSH.

## Workflow

```
локальні зміни → git commit → git push → bash deploy.sh → serhii.blog
```

