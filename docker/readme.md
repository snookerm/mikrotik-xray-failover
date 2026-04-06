# 🐳 Сборка Docker-образа для MikroTik вручную

## 📌 Описание
В этом руководстве описано, как:
- использовать готовый Docker-образ
- собрать `.tar` образ вручную
- корректно подготовить образ для MikroTik (RouterOS)

---

## 🚀 Быстрый запуск контейнера в MikroTik
На примере контейнера xray:
```routeros
/container/add file=usb1-part1/xray-images/xray-mikrotik-26.2.9-arm64.tar \
    interface=docker-xray-veth \
    root-dir=usb1-part1/xray-root \
    mountlists=XRAYCFG \
    name=xray \
    logging=yes \
    start-on-boot=yes
```

---

## 📦 Готовый образ

Можно скачать уже собранный образ:

👉 http://any.hayazg.net/xray-mikrotik-26.2.9-arm64.tar

---

## ⚠️ Важно (Windows + Docker Desktop)

Образы, собранные через Docker Desktop, часто **НЕ совместимы** с MikroTik.

Причины:
- несовместимый формат `docker save`
- дополнительные metadata (provenance / SBOM)
- особенности `buildx`

📎 Обсуждение:
https://forum.mikrotik.com/t/container-cant-running-on-arm64-routeros/164606

👉 MikroTik рекомендует использовать **Podman**

---

# 🪟 Сборка в Windows (через Podman + WSL2)

## 1. Установка Podman
Скачать:
https://github.com/containers/podman/releases

Установка в:
```
C:\Program Files\RedHat\Podman\
```

---

## 2. Добавление в PATH

### Временно (PowerShell)
```powershell
$env:PATH += ";C:\Program Files\RedHat\Podman\"
```

### Постоянно
1. Win + R → `sysdm.cpl`
2. Вкладка **Дополнительно → Переменные среды**
3. Добавить в `Path`:
```
C:\Program Files\RedHat\Podman\
```

Перезапустить PowerShell

Проверка (PowerShell):
```powershell
$env:PATH -split ";"
```

---

## 3. Инициализация Podman (PowerShell)

```powershell
podman machine init
podman machine start
podman --version
```

---

## 4. Сборка образа (PowerShell)

```powershell
cd e:\xray-mikrotik

podman build --platform linux/arm64 --tag xray-mikrotik:26.2.9 .

podman save --format docker-archive -o xray-mikrotik-26.2.9-arm64.tar xray-mikrotik:26.2.9
```

📁 Готовый файл:
```
E:\xray-mikrotik\xray-mikrotik-26.2.9-arm64.tar
```

➡️ Перенести в MikroTik:
```
usb1-part1/xray-images/
```

---

# 🐧 Сборка в Linux (Ubuntu)

## 1. Установка и подготовка

```bash
sudo mkdir -p /opt/mikrotik
cd /opt/mikrotik

sudo apt install podman

podman pull --arch=arm64 docker.io/snookerm/xray-mikrotik:26.2.9

podman save snookerm/xray-mikrotik:26.2.9 > xray-mikrotik-26.2.9-arm64.tar
```

---

## 2. Перенос файла

Файл:
```
/opt/mikrotik/xray-mikrotik-26.2.9-arm64.tar
```

➡️ Перенести в MikroTik:
```
usb1-part1/xray-images/
```

---

## 3. Пересборка контейнера в MikroTik
На примере контейнера xray:
```routeros
/container/stop xray
/container/remove xray

/container/add file=usb1-part1/xray-images/xray-mikrotik-26.2.9-arm64.tar \
    interface=docker-xray-veth \
    root-dir=usb1-part1/xray-root \
    mountlists=XRAYCFG \
    name=xray \
    logging=yes \
    start-on-boot=yes
```

---

# ✅ Итог

✔ Используйте **Podman**, а не Docker Desktop  
✔ Собирайте образ под `linux/arm64`  
✔ Храните `.tar` на USB (надежнее)  
✔ Используйте готовый образ или кастомизируйте через Dockerfile  

---

# 💡 Полезно

- Можно кастомизировать `Dockerfile` и `start.sh`
- Можно хранить несколько образов на флешке
- Подходит для оффлайн-инсталляций

---

