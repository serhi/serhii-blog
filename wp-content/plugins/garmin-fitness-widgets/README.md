# Garmin Fitness Widgets

WordPress плагін, який відображає дані Garmin Connect (вага, остання активність, відстань за тиждень) через shortcode.

Дані оновлюються Python-скриптом раз на добу. WordPress лише читає `garmin_data.json` — жодних прямих запитів до Garmin.

---

## Вимоги

- PHP 7.4+
- Python 3.8+
- `pip install garminconnect`

---

## Встановлення плагіну

1. Скопіюй папку `garmin-fitness-widgets/` у `wp-content/plugins/`
2. Активуй плагін у **WP Admin → Плагіни**

---

## Перший запуск Python-скрипту (збереження токенів)

```bash
cd wp-content/plugins/garmin-fitness-widgets
GARMIN_EMAIL=you@email.com GARMIN_PASSWORD=secret python3 fetch_garmin.py
```

При першому запуску скрипт логіниться з паролем і зберігає OAuth-токени у `~/.garminconnect`. Наступні запуски використовують збережені токени — пароль не передається повторно.

Після успішного запуску у папці плагіну з'явиться `garmin_data.json`.

Переконайся, що файл має права на читання для веб-сервера:
```bash
chmod 644 garmin_data.json
```

---

## Налаштування cron (автоматичне оновлення)

Додай у crontab на сервері (`crontab -e`):

```bash
0 6 * * * cd /var/www/html/wp-content/plugins/garmin-fitness-widgets && GARMIN_EMAIL=you@email.com GARMIN_PASSWORD=secret python3 fetch_garmin.py >> /var/log/garmin-fetch.log 2>&1
```

Це запускатиме скрипт щодня о 06:00 і писатиме лог у `/var/log/garmin-fetch.log`.

---

## Shortcodes

| Shortcode | Що відображає |
|---|---|
| `[garmin_weight]` | Картка ваги з трендом |
| `[garmin_last_activity]` | Картка останньої активності |
| `[garmin_weekly_distance]` | Картка відстані за тиждень |
| `[garmin_widgets]` | Всі три картки у ряд |

### Приклад використання

Вставте у будь-який пост або сторінку:

```
[garmin_widgets]
```

або окремі віджети:

```
[garmin_weight]
[garmin_last_activity]
[garmin_weekly_distance]
```

---

## Кастомізація стилів

Перевизнач CSS-змінні у своїй темі:

```css
:root {
  --gfw-bg: #ffffff;       /* фон картки */
  --gfw-border: #e5e7eb;   /* рамка */
  --gfw-text: #111827;     /* основний текст */
  --gfw-muted: #6b7280;    /* другорядний текст */
  --gfw-accent: #3b82f6;   /* акцент (стабільний тренд) */
  --gfw-up: #ef4444;       /* тренд вгору (червоний) */
  --gfw-down: #22c55e;     /* тренд вниз (зелений) */
}
```

---

## Безпека

- Credentials Garmin **ніколи** не потрапляють у PHP або JavaScript.
- PHP-код читає лише локальний JSON-файл — жодних зовнішніх запитів.
- Якщо `garmin_data.json` відсутній або пошкоджений — shortcode повертає порожній рядок без помилок.
