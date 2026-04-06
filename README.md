# 🚀 MikroTik Xray Failover Gateway

![RouterOS](https://img.shields.io/badge/RouterOS-7.x-blue)
![Architecture](https://img.shields.io/badge/Architecture-ARM64-green)
![Xray](https://img.shields.io/badge/Xray-VLESS%20Reality-orange)
![Status](https://img.shields.io/badge/Status-Stable-brightgreen)

Transparent Xray failover gateway for MikroTik RouterOS

---

## 📚 Содержание

- [Описание проекта](#-описание)
- [Возможности](#%EF%B8%8F-возможности)
- [Преднастройка](#-2-введение-и-преднастройка-routeros)
- [Контейнеры](#-4-подготовка-флешки-и-контейнеров)
- [Failover](#-5-failover-логика)

---

## 📌 Описание

Failover-прокси шлюз на MikroTik с использованием Xray (VLESS + Reality + XHTTP).

✔ Transparent proxy  
✔ Auto failover / failback  
✔ Telegram уведомления  
✔ Полностью автономная работа  

---

## ⚙️ Возможности

- 🔁 Автоматическое переключение между 4 Xray серверами
- 🔄 Возврат на основной сервер
- 🌐 Policy routing
- 📡 Transparent proxy (без настройки клиентов)
- 📢 Telegram уведомления
- 💾 Работает с USB (переносимо)

---

## ⚠️ Ограничения

- ❌ Нет UDP (dokodemo-door)
- ❌ WhatsApp calls (Windows) могут не работать

---

## 🧠 Архитектура

```mermaid
flowchart TD
    LAN --> MikroTik
    MikroTik --> Routing[r_to_vpn]
    Routing --> Container
    Container --> Xray
    Xray --> Internet
```

---

## 🐳 Containers + VETH + USB + запуск Xray

В этой схеме MikroTik выступает как прозрачный шлюз, а Xray запускается внутри контейнеров RouterOS.

Используются:

- RouterOS container subsystem
- 4 отдельных контейнера Xray
- 4 отдельных `veth` интерфейса
- USB-накопитель для хранения 4 root-dir контейнеров и tmp-файлов
- 4 отдельные директории с `config.json` в NAND (внутренней памяти) Mikrotik
- 8 `Netwatch` для local/remote health-check-ов
- 2 `Scheduler` для запуска `update-watch-hosts` при startup и каждые 30 минут
- скрипт `reconcile-xray` для выбора лучшего доступного узла
- скрипт `update-watch-hosts` для автоматическое обновление IP-адресов для Netwatch на основе доменных имён из конфигов Xray.

---

## 📦 Подготовка USB-накопителя

Рекомендуется использовать отдельную USB-флешку, отформатированную в `ext4`.

Пример форматирования:

```routeros
/disk format-drive usb1 file-system=ext4
```
Пример JSON

```json
{
  "name": "example",
  "version": "1.0",
  "enabled": true
}
```
