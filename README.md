# 🚀 MikroTik Xray Failover Gateway

![RouterOS](https://img.shields.io/badge/RouterOS-7.x-blue)
![Architecture](https://img.shields.io/badge/Architecture-ARM64-green)
![Xray](https://img.shields.io/badge/Xray-VLESS%20Reality-orange)
![Status](https://img.shields.io/badge/Status-Stable-brightgreen)

Transparent Xray failover gateway for MikroTik RouterOS

---

## 📚 Содержание

- [Описание проекта](#-1-описание-проекта)
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
- USB-накопитель для хранения root-dir контейнеров
- отдельные директории с `config.json`
- `Netwatch` для local/remote health-check
- скрипт `reconcile-xray` для выбора лучшего доступного узла

---

## 📦 Подготовка USB-накопителя

Рекомендуется использовать отдельную USB-флешку, отформатированную в `ext4`.

Пример форматирования:

```routeros
/disk format-drive usb1 file-system=ext4
