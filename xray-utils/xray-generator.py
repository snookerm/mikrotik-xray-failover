#!/usr/bin/env python3
from __future__ import annotations
import json
import sys
from pathlib import Path
from typing import Any

def first_assoc(arr: Any) -> dict[str, Any]:
    if isinstance(arr, list) and arr and isinstance(arr[0], dict):
        return arr[0]
    return {}

def array_get(data: Any, path: list[Any], default: Any = None) -> Any:
    cur = data
    for key in path:
        if not isinstance(cur, (dict, list)):
            return default
        try:
            cur = cur[key]
        except (KeyError, IndexError, TypeError):
            return default
    return cur

def normalize_string_or_array(value: Any) -> list[Any]:
    if isinstance(value, list):
        return value
    if isinstance(value, str) and value:
        return [value]
    return []

def find_proxy_outbound(src: dict[str, Any]) -> dict[str, Any]:
    outbounds = src.get("outbounds", [])
    if not isinstance(outbounds, list):
        return {}

    for ob in outbounds:
        if not isinstance(ob, dict):
            continue
        proto = str(ob.get("protocol", "")).lower()
        tag = str(ob.get("tag", "")).lower()
        if proto == "vless" and tag in {"proxy", "vless", "main", "out"}:
            return ob

    for ob in outbounds:
        if isinstance(ob, dict) and str(ob.get("protocol", "")).lower() == "vless":
            return ob

    for ob in outbounds:
        if not isinstance(ob, dict):
            continue
        proto = str(ob.get("protocol", "")).lower()
        if proto not in {"freedom", "blackhole", "dns"}:
            return ob

    return {}

def build_mikrotik_config(src: dict[str, Any]) -> dict[str, Any]:
    proxy = find_proxy_outbound(src)
    vnext = first_assoc(array_get(proxy, ["settings", "vnext"], []))
    user = first_assoc(array_get(vnext, ["users"], []))

    src_stream = proxy.get("streamSettings", {}) if isinstance(proxy.get("streamSettings"), dict) else {}
    reality = src_stream.get("realitySettings", {}) if isinstance(src_stream.get("realitySettings"), dict) else {}
    xhttp = src_stream.get("xhttpSettings", {}) if isinstance(src_stream.get("xhttpSettings"), dict) else {}
    sockopt = src_stream.get("sockopt", {}) if isinstance(src_stream.get("sockopt"), dict) else {}

    dns_servers = normalize_string_or_array(array_get(src, ["dns", "servers"], []))
    if not dns_servers:
        dns_servers = ["1.1.1.1", "1.0.0.1", "8.8.8.8", "9.9.9.9"]

    routing = src.get("routing", {}) if isinstance(src.get("routing"), dict) else {}
    rules = routing.get("rules", []) if isinstance(routing.get("rules"), list) else []

    has_private = False
    for rule in rules:
        if not isinstance(rule, dict):
            continue
        ips = rule.get("ip", [])
        if isinstance(ips, list) and "geoip:private" in ips:
            has_private = True
            break

    if not has_private:
        rules.insert(0, {
            "type": "field",
            "ip": ["geoip:private"],
            "outboundTag": "direct",
        })

    return {
        "log": {
            "loglevel": str(array_get(src, ["log", "loglevel"], "warning")),
        },
        "dns": {
            "servers": dns_servers,
            "queryStrategy": str(array_get(src, ["dns", "queryStrategy"], "UseIPv4")),
        },
        "inbounds": [
            {
                "tag": "redir-in",
                "port": 12345,
                "protocol": "dokodemo-door",
                "settings": {
                    "network": "tcp",
                    "followRedirect": True,
                },
                "streamSettings": {
                    "sockopt": {
                        "tproxy": "redirect",
                    },
                },
                "sniffing": {
                    "enabled": True,
                    "routeOnly": True,
                    "destOverride": ["http", "tls"],
                },
            },
            {
                "tag": "health-in",
                "listen": "0.0.0.0",
                "port": 15443,
                "protocol": "socks",
                "settings": {
                    "auth": "noauth",
                    "udp": False,
                },
            },
        ],
        "outbounds": [
            {
                "tag": "proxy",
                "protocol": str(proxy.get("protocol", "vless")).lower() or "vless",
                "settings": {
                    "vnext": [
                        {
                            "address": str(vnext.get("address", "example.com")),
                            "port": int(vnext.get("port", 443)),
                            "users": [
                                {
                                    "id": str(user.get("id", "00000000-0000-0000-0000-000000000000")),
                                    "encryption": str(user.get("encryption", "none")),
                                    "flow": str(user.get("flow", "")),
                                }
                            ],
                        }
                    ]
                },
                "streamSettings": {
                    "network": str(src_stream.get("network", "xhttp")),
                    "security": str(src_stream.get("security", "reality")),
                    "realitySettings": {
                        "fingerprint": str(reality.get("fingerprint", "chrome")),
                        "publicKey": str(reality.get("publicKey", "REPLACE_ME")),
                        "serverName": str(reality.get("serverName", "example.com")),
                        "shortId": str(reality.get("shortId", "")),
                        "show": bool(reality.get("show", False)),
                    },
                    "sockopt": {
                        "domainStrategy": str(sockopt.get("domainStrategy", "AsIs")),
                        "tcpFastOpen": bool(sockopt.get("tcpFastOpen", True)),
                        "tcpNoDelay": bool(sockopt.get("tcpNoDelay", True)),
                        "tcpUserTimeout": int(sockopt.get("tcpUserTimeout", 15000)),
                        "tcpKeepAliveIdle": int(sockopt.get("tcpKeepAliveIdle", 120)),
                        "tcpKeepAliveInterval": int(sockopt.get("tcpKeepAliveInterval", 20)),
                    },
                    "xhttpSettings": {
                        "host": str(xhttp.get("host", "")),
                        "mode": str(xhttp.get("mode", "packet-up")),
                        "extra": xhttp.get("extra", {}) if isinstance(xhttp.get("extra"), dict) else {},
                    },
                },
            },
            {
                "tag": "direct",
                "protocol": "freedom",
                "settings": {
                    "domainStrategy": "UseIPv4",
                },
            },
            {
                "tag": "block",
                "protocol": "blackhole",
            },
        ],
        "routing": {
            "domainMatcher": str(routing.get("domainMatcher", "hybrid")),
            "domainStrategy": str(routing.get("domainStrategy", "IPIfNonMatch")),
            "rules": rules,
        },
        "policy": src.get("policy") if isinstance(src.get("policy"), dict) else {
            "levels": {
                "0": {
                    "handshake": 4,
                    "connIdle": 120,
                    "uplinkOnly": 30,
                    "downlinkOnly": 30,
                }
            }
        },
    }

def main() -> int:
    print("Paste Xray/HAPP JSON, then press Ctrl+D (Ubuntu/Linux).")
    raw = sys.stdin.read()
    if not raw.strip():
        print("No input received.", file=sys.stderr)
        return 1

    try:
        src = json.loads(raw)
    except json.JSONDecodeError as exc:
        print(f"Invalid JSON: {exc}", file=sys.stderr)
        return 1

    if not isinstance(src, dict):
        print("Top-level JSON must be an object.", file=sys.stderr)
        return 1

    mikrotik_config = build_mikrotik_config(src)

    out_client = Path("config_from_xray_client.json")
    out_mikrotik = Path("config.json")

    out_client.write_text(json.dumps(src, ensure_ascii=False, indent=2), encoding="utf-8")
    out_mikrotik.write_text(json.dumps(mikrotik_config, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"Saved original client JSON to: {out_client.resolve()}")
    print(f"Saved MikroTik-compatible JSON to: {out_mikrotik.resolve()}")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
