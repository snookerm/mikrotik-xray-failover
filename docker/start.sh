#!/bin/sh
iptables -t nat -F
iptables -t nat -A PREROUTING -p tcp --dport 15443 -j RETURN
iptables -t nat -A PREROUTING -p tcp -j REDIRECT --to-ports 12345
exec /usr/bin/xray -config /etc/xray/config.json
