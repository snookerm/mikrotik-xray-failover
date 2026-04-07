# MikroTik Scripts

RouterOS scripts for managing Xray container failover logic.

## Scripts

### reconcile-xray.rsc

Main failover script. Called automatically via Netwatch when server status changes.

**Functions:**
- Checks statuses of 8 Netwatch entries (4 local + 4 remote)
- Determines the best available server by priority: main > backup1 > backup2 > backup3
- Switches routes in the `r_to_vpn` table
- Sends Telegram notifications via proxy server

**Configuration before use:**

Replace at the beginning of the script:

| Variable      | Description                     | Example                         |
|---------------|----------------------------------|---------------------------------|
| `token`       | Telegram bot token               | `123456:ABC-DEF...`             |
| `chat`        | Group/chat ID                    | `-1001234567890`                |
| `thread`      | Thread ID (for forums)           | `123`                           |
| `proxyBase`   | Telegram proxy URL               | `https://proxy.example.com`     |

### reconcile-xray-without-proxy.rsc

Analog of `reconcile-xray.rsc`, but sends notifications directly to `api.telegram.org`.

Use this version if MikroTik has direct access to the Telegram API.

**Difference:** instead of `proxyBase`, the direct URL `https://api.telegram.org/bot...` is used.

### update-watch-hosts.rsc

Automatically updates IP addresses in Netwatch from domain names specified in Xray configs.

**How it works:**
1. Reads the `config.json` file from each directory (`xray-configs/`, `xray-configs2/`, `xray-configs3/`, `xray-configs4/`)
2. Extracts the value of the `"address"` field (server domain name)
3. Resolves the domain to IP via `:resolve`
4. Updates `host` and `comment` in the corresponding Netwatch entry

**Mapping:**

| Config File                 | Netwatch Entry        |
|-----------------------------|-----------------------|
| `xray-configs/config.json`  | `watch-xray-main`     |
| `xray-configs2/config.json` | `watch-xray-backup1`  |
| `xray-configs3/config.json` | `watch-xray-backup2`  |
| `xray-configs4/config.json` | `watch-xray-backup3`  |

**Execution:**
- Automatically via Scheduler on router startup
- Periodically every 30 minutes
- Manually after adding Remote Netwatch entries:

```routeros
/system script run update-watch-hosts
```

## Installing Scripts

Scripts are added to RouterOS via:

```routeros
/system/script
add name=reconcile-xray source=[contents of reconcile-xray.rsc file]
add name=update-watch-hosts source=[contents of update-watch-hosts.rsc file]
```

Detailed installation and configuration instructions: [README.md](../README.en.md)
