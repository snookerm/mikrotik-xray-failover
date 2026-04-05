# 🧩 Xray Config Generators

В проекте доступны два генератора конфигов:

- 🌐 Web-генератор (PHP)
- 🖥 CLI-генератор (Python)

Оба позволяют:
- вставить JSON из клиента (например HAPP)
- получить **MikroTik-совместимый `config.json`**
- сохранить оригинальный конфиг отдельно

---

# 🌐 Web Generator (PHP)

📍 Файл:  
https://github.com/snookerm/mikrotik-xray-failover/blob/main/xray-utils/xray-generator.php  

## 📌 Как использовать

1. Залей файл на свой сервер:
```
/xray-config-generator/xray-generator.php
```

2. Открой в браузере:
```
https://your-domain/xray-config-generator/xray-generator.php
```

3. Вставь JSON из клиента (HAPP / v2ray / xray)

4. Нажми:
```
Generate config.json
```

5. Скачай файл

---

## ✅ Что делает

- извлекает `vnext`
- переносит:
  - address
  - port
  - uuid
  - reality настройки
- добавляет:
  - dokodemo-door inbound (12345)
  - health inbound (15443)
- сохраняет совместимость с MikroTik контейнером

---

# 🖥 CLI Generator (Python)

📍 README:  
https://github.com/snookerm/mikrotik-xray-failover/blob/main/xray-utils/readme.md  

📍 Скрипт:  
https://github.com/snookerm/mikrotik-xray-failover/blob/main/xray-utils/xray-generator.py  

---

## 📌 Установка (Ubuntu 22.04)

### 1. Создать папку
```bash
sudo mkdir -p /opt/xray-generator
```

### 2. Перейти в неё
```bash
cd /opt/xray-generator
```

### 3. Получить скрипт

👉 Вариант 1 (скачать с GitHub):
```bash
wget https://raw.githubusercontent.com/snookerm/mikrotik-xray-failover/main/xray-utils/xray-generator.py
```

👉 Вариант 2 (создать вручную):
```bash
nano xray-generator.py
```

### 4. Дать права на исполнение
```bash
chmod +x xray-generator.py
```

---

## ▶️ Запуск

```bash
cd /opt/xray-generator
./xray-generator.py
```

или:

```bash
python3 xray-generator.py
```

---

## 🧠 Как пользоваться

1. Запускаешь скрипт  
2. Вставляешь JSON из клиента  
3. Нажимаешь:

```
Ctrl + D
```

---

## 📂 Результат

Создаются 2 файла:

```
config.json                     # для MikroTik
config_from_xray_client.json    # оригинал
```

---

## ⚡ Быстрый режим (из файла)

```bash
python3 xray-generator.py < client.json
```

---

# 🔁 Использование для failover (4 сервера)

Повторить генерацию 4 раза и сохранить:

```
xray-configs/config.json
xray-configs2/config.json
xray-configs3/config.json
xray-configs4/config.json
```

---

# 💡 Рекомендации

- Не публикуйте:
  - UUID
  - publicKey
  - shortId
- Используйте `.example.json` для GitHub
- Проверяйте config через:
```bash
xray -test -config config.json
```

---

# 🚀 Итог

| Генератор | Где использовать | Уровень |
|----------|----------------|--------|
| PHP      | Веб (быстро)   | ⭐⭐⭐ |
| Python   | CLI / сервер   | ⭐⭐⭐⭐ |
