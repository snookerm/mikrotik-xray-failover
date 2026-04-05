# 🧩 Xray Config Generators for MikroTik

[![Platform](https://img.shields.io/badge/platform-MikroTik-blue)]()
[![Xray](https://img.shields.io/badge/Xray-VLESS%20Reality-green)]()
[![License](https://img.shields.io/badge/license-MIT-lightgrey)]()
[![Status](https://img.shields.io/badge/status-stable-success)]()

---

## 🚀 Быстрый старт

👉 Воспользуйтесь бесплатным генератором Xray для MikroTik:  
https://any.hayazg.net/xray-generator.php  

Или настройте свой генератор 👇

---

## 📑 Содержание (TOC)

- [🧩 О проекте](#-о-проекте)
- [⚙️ Варианты генерации](#️-варианты-генерации)
  - [🐍 Вариант 1 — Python](#-вариант-1--python)
  - [🌐 Вариант 2 — PHP](#-вариант-2--php)
- [📂 Что генерируется](#-что-генерируется)
- [🔁 Использование для failover](#-использование-для-failover)
- [📸 Скриншоты](#-скриншоты)
- [💡 Рекомендации](#-рекомендации)

---

## 🧩 О проекте

Этот инструмент позволяет:

✅ Преобразовать JSON из Xray клиента (например HAPP)  
✅ Получить MikroTik-совместимый config.json  
✅ Упростить настройку Xray контейнеров  
✅ Подготовить конфиги для failover (multi-Xray)

---

## ⚙️ Варианты генерации

---

## 🐍 Вариант 1 — Python

📍 Скрипт:  
https://github.com/snookerm/mikrotik-xray-failover/blob/main/xray-utils/xray-generator.py  

### 📦 Установка (Ubuntu 22.04)

sudo mkdir -p /opt/xray-generator  
cd /opt/xray-generator  

### 📥 Скачать скрипт

wget https://raw.githubusercontent.com/snookerm/mikrotik-xray-failover/main/xray-utils/xray-generator.py  

### 🔧 Права

chmod +x xray-generator.py  

---

### ▶️ Запуск

./xray-generator.py  

или:

python3 xray-generator.py  

---

### 🧠 Использование

1. Запускаешь скрипт  
2. Вставляешь JSON из клиента  
3. Нажимаешь Ctrl + D  

---

### 📂 Результат

config.json  
config_from_xray_client.json  

---

### ⚡ Быстрый режим

python3 xray-generator.py < client.json  

---

## 🌐 Вариант 2 — PHP

📍 Файл:  
https://github.com/snookerm/mikrotik-xray-failover/blob/main/xray-utils/xray-generator.php  

---

### 📌 Установка

Залить файл на сервер:

/xray-config-generator/xray-generator.php  

---

### 🌍 Использование

https://your-domain/xray-config-generator/xray-generator.php  

---

### 🧠 Как пользоваться

1. Вставить JSON из клиента  
2. Нажать Generate config.json  
3. Скачать файл  

---

## 📂 Что генерируется

- address  
- port  
- uuid  
- reality настройки  
- inbound dokodemo-door (12345)  
- inbound health-check (15443)  

---

## 🔁 Использование для failover

xray-configs/config.json  
xray-configs2/config.json  
xray-configs3/config.json  
xray-configs4/config.json  

---

## 📸 Скриншоты

Создайте папку:

images/

И добавьте:

images/web-generator.png  
images/cli-generator.png  

---

## 💡 Рекомендации

❗ Не публикуйте:
- UUID  
- publicKey  
- shortId  

✔ Используйте example конфиги  

✔ Проверяйте:

xray -test -config config.json  

---

## ⭐ Итог

| Генератор | Где использовать | Уровень |
|----------|----------------|--------|
| PHP      | Веб (быстро)   | ⭐⭐⭐ |
| Python   | CLI / сервер   | ⭐⭐⭐⭐ |

---

## 🚀 Идея проекта

👉 вставил JSON → получил config → загрузил в MikroTik → работает
