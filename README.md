# 🚀 MikroTik Xray Failover Gateway

![RouterOS](https://img.shields.io/badge/RouterOS-7.x-blue)
![Architecture](https://img.shields.io/badge/Architecture-ARM64-green)
![Xray](https://img.shields.io/badge/Xray-VLESS%20Reality-orange)
![Status](https://img.shields.io/badge/Status-Stable-brightgreen)
Transparent Xray failover gateway for MikroTik RouterOS
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
