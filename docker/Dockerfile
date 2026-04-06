FROM teddysun/xray:26.2.6

RUN apk add --no-cache iptables

COPY start.sh /start.sh
RUN chmod +x /start.sh

CMD ["sh", "/start.sh"]
