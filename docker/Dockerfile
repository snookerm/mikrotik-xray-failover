FROM teddysun/xray:26.5.3

RUN apk add --no-cache iptables-legacy || apk add --no-cache iptables

COPY start.sh /start.sh
RUN chmod +x /start.sh

CMD ["sh", "/start.sh"]
