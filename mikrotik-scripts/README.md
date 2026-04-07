# MikroTik Scripts

RouterOS-скрипты для управления failover-логикой Xray-контейнеров.

## Скрипты

### reconcile-xray.rsc

Основной failover-скрипт. Вызывается автоматически через Netwatch при изменении статуса серверов.

**Функции:**
- Проверяет статусы 8 Netwatch-записей (4 local + 4 remote)
- Определяет лучший доступный сервер по приоритету: main > backup1 > backup2 > backup3
- Переключает маршруты в таблице `r_to_vpn`
- Отправляет Telegram-уведомления через прокси-сервер

**Настройка перед использованием:**

Замените в начале скрипта:

| Переменная    | Описание                        | Пример                          |
|---------------|----------------------------------|---------------------------------|
| `token`       | Токен Telegram-бота              | `123456:ABC-DEF...`             |
| `chat`        | ID группы/чата                   | `-1001234567890`                |
| `thread`      | ID темы (для форумов)            | `123`                           |
| `proxyBase`   | URL Telegram-прокси              | `https://proxy.example.com`     |

### reconcile-xray-without-proxy.rsc

Аналог `reconcile-xray.rsc`, но отправляет уведомления напрямую в `api.telegram.org`.

Используйте эту версию, если MikroTik имеет прямой доступ к Telegram API.

**Отличие:** вместо `proxyBase` используется прямой URL `https://api.telegram.org/bot...`.

### update-watch-hosts.rsc

Автоматически обновляет IP-адреса в Netwatch из доменных имен, указанных в конфигах Xray.

**Принцип работы:**
1. Читает файл `config.json` из каждой директории (`xray-configs/`, `xray-configs2/`, `xray-configs3/`, `xray-configs4/`)
2. Извлекает значение поля `"address"` (доменное имя сервера)
3. Резолвит домен в IP через `:resolve`
4. Обновляет `host` и `comment` в соответствующей Netwatch-записи

**Соответствие:**

| Файл конфига                | Netwatch-запись       |
|-----------------------------|-----------------------|
| `xray-configs/config.json`  | `watch-xray-main`     |
| `xray-configs2/config.json` | `watch-xray-backup1`  |
| `xray-configs3/config.json` | `watch-xray-backup2`  |
| `xray-configs4/config.json` | `watch-xray-backup3`  |

**Запуск:**
- Автоматически через Scheduler при старте роутера
- Периодически каждые 30 минут
- Вручную после добавления Remote Netwatch-записей:

```routeros
/system script run update-watch-hosts
```

## Установка скриптов

Скрипты добавляются в RouterOS через:

```routeros
/system/script
add name=reconcile-xray source=[содержимое файла reconcile-xray.rsc]
add name=update-watch-hosts source=[содержимое файла update-watch-hosts.rsc]
```

Подробная инструкция по установке и настройке: [README.md](../README.md)
