# 🚀 Telegram Proxy Server (Nginx + Let's Encrypt)

Простая инструкция по развёртыванию прокси для Telegram Bot API на Ubuntu за ~5 минут.
Telegram-proxy нужен для того, чтоб работали уведомления, отправленные из Mikrotik.
Подходит даже для самых дешёвых VPS где-нибудь в Германии или Финляндии.

---

## 📋 Требования

- OS: Ubuntu
- Домен: domain.tld
- Поддомен: proxy.domain.tld
- Публичный IP: <PUBLIC_SERVER_IP>

---

## 🌐 0. DNS

```
proxy.domain.tld.       A   <PUBLIC_SERVER_IP>
www.proxy.domain.tld.   A   <PUBLIC_SERVER_IP>
```

---

## 📦 1. Установка

```
sudo apt update
sudo apt install -y nginx certbot python3-certbot-nginx
```

---

## 🔥 2. Firewall

```
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## ⚙️ 3. Конфиг nginx (HTTP)

```
server {
    listen 80;
    server_name proxy.domain.tld;

    location ~ ^/bot/(.*)$ {
        proxy_pass https://api.telegram.org/$1$is_args$args;

        proxy_set_header Host api.telegram.org;
        proxy_ssl_server_name on;
        proxy_ssl_name api.telegram.org;
    }

    location / {
        return 404;
    }
}
```

---

## 🔗 4. Активация

```
sudo ln -s /etc/nginx/sites-available/proxy.domain.tld /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 🔐 5. SSL

```
sudo certbot --nginx -d proxy.domain.tld
```

---

## 🔒 6. HTTPS конфиг

```
server {
    listen 80;
    server_name proxy.domain.tld;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name proxy.domain.tld;

    ssl_certificate /etc/letsencrypt/live/proxy.domain.tld/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/proxy.domain.tld/privkey.pem;

    location ~ ^/bot/(.*)$ {
        proxy_pass https://api.telegram.org/$1$is_args$args;

        proxy_set_header Host api.telegram.org;
    }

    location / {
        return 404;
    }
}
```

---

## ✅ Проверка

```
curl -I https://proxy.domain.tld
```

```
curl -i "https://proxy.domain.tld/bot/bot<TOKEN>/getMe"
```

---

## 📡 MikroTik

```
/tool fetch url="https://proxy.domain.tld/bot/bot<TOKEN>/sendMessage?chat_id=<ID>&text=test" http-percent-encoding=yes output=user as-value check-certificate=no
```

---

## 🛡️ Безопасность

- fail2ban
- SSH ключи
- отключить root

---
