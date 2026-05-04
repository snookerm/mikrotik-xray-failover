<?php
declare(strict_types=1);

/**
 * Xray JSON -> MikroTik-compatible config.json generator
 * Single-file PHP app for PHP 8.1+
 * Place at: /xray-config-generator/xray-generator.php
 */

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pretty_json(array $data): string {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
}

function first_assoc(array $arr): array {
    return isset($arr[0]) && is_array($arr[0]) ? $arr[0] : [];
}

function array_get(array $a, array $path, mixed $default = null): mixed {
    $cur = $a;
    foreach ($path as $key) {
        if (!is_array($cur) || !array_key_exists($key, $cur)) {
            return $default;
        }
        $cur = $cur[$key];
    }
    return $cur;
}

function normalize_string_or_array(mixed $v): array {
    if (is_array($v)) return $v;
    if (is_string($v) && $v !== '') return [$v];
    return [];
}

function find_proxy_outbound(array $src): array {
    $outbounds = $src['outbounds'] ?? [];
    if (!is_array($outbounds)) return [];

    foreach ($outbounds as $ob) {
        if (!is_array($ob)) continue;
        $proto = strtolower((string)($ob['protocol'] ?? ''));
        $tag = strtolower((string)($ob['tag'] ?? ''));
        if ($proto === 'vless' && in_array($tag, ['proxy', 'vless', 'main', 'out'], true)) {
            return $ob;
        }
    }
    foreach ($outbounds as $ob) {
        if (is_array($ob) && strtolower((string)($ob['protocol'] ?? '')) === 'vless') {
            return $ob;
        }
    }
    foreach ($outbounds as $ob) {
        if (!is_array($ob)) continue;
        $proto = strtolower((string)($ob['protocol'] ?? ''));
        if (!in_array($proto, ['freedom', 'blackhole', 'dns'], true)) {
            return $ob;
        }
    }
    return [];
}

function build_mikrotik_config(array $src): array {
    $proxy = find_proxy_outbound($src);

    $vnext = first_assoc(array_get($proxy, ['settings', 'vnext'], []));
    $user  = first_assoc(array_get($vnext, ['users'], []));

    $srcStream = is_array($proxy['streamSettings'] ?? null) ? $proxy['streamSettings'] : [];
    $reality   = is_array($srcStream['realitySettings'] ?? null) ? $srcStream['realitySettings'] : [];
    $xhttp     = is_array($srcStream['xhttpSettings'] ?? null) ? $srcStream['xhttpSettings'] : [];
    $sockopt   = is_array($srcStream['sockopt'] ?? null) ? $srcStream['sockopt'] : [];

    $dnsServers = normalize_string_or_array(array_get($src, ['dns', 'servers'], []));
    if ($dnsServers === []) {
        $dnsServers = ['1.1.1.1', '1.0.0.1', '8.8.8.8', '9.9.9.9'];
    }

    $dns = [
        'servers' => $dnsServers,
        'queryStrategy' => (string)(array_get($src, ['dns', 'queryStrategy'], 'UseIPv4')),
    ];

    $proxyOutbound = [
        'tag' => 'proxy',
        'protocol' => strtolower((string)($proxy['protocol'] ?? 'vless')) ?: 'vless',
        'settings' => [
            'vnext' => [[
                'address' => (string)($vnext['address'] ?? 'example.com'),
                'port' => (int)($vnext['port'] ?? 443),
                'users' => [[
                    'id' => (string)($user['id'] ?? '00000000-0000-0000-0000-000000000000'),
                    'encryption' => (string)($user['encryption'] ?? 'none'),
                    'flow' => (string)($user['flow'] ?? ''),
                ]],
            ]],
        ],
        'streamSettings' => [
            'network' => (string)($srcStream['network'] ?? 'xhttp'),
            'security' => (string)($srcStream['security'] ?? 'reality'),
            'realitySettings' => [
                'fingerprint' => (string)($reality['fingerprint'] ?? 'chrome'),
                'publicKey' => (string)($reality['publicKey'] ?? 'REPLACE_ME'),
                'serverName' => (string)($reality['serverName'] ?? 'example.com'),
                'shortId' => (string)($reality['shortId'] ?? ''),
                'show' => (bool)($reality['show'] ?? false),
            ],
            'sockopt' => [
                'domainStrategy' => (string)($sockopt['domainStrategy'] ?? 'AsIs'),
                'tcpFastOpen' => (bool)($sockopt['tcpFastOpen'] ?? true),
                'tcpNoDelay' => (bool)($sockopt['tcpNoDelay'] ?? true),
                'tcpUserTimeout' => (int)($sockopt['tcpUserTimeout'] ?? 15000),
                'tcpKeepAliveIdle' => (int)($sockopt['tcpKeepAliveIdle'] ?? 120),
                'tcpKeepAliveInterval' => (int)($sockopt['tcpKeepAliveInterval'] ?? 20),
            ],
            'xhttpSettings' => [
                'host' => (string)($xhttp['host'] ?? ''),
                'mode' => (string)($xhttp['mode'] ?? 'packet-up'),
                'extra' => is_array($xhttp['extra'] ?? null) ? $xhttp['extra'] : (object)[],
            ],
        ],
    ];

    $routing = is_array($src['routing'] ?? null) ? $src['routing'] : [];
    $rules = is_array($routing['rules'] ?? null) ? $routing['rules'] : [];

    $hasPrivate = false;
    foreach ($rules as $rule) {
        if (!is_array($rule)) continue;
        $ips = $rule['ip'] ?? [];
        if (is_array($ips) && in_array('geoip:private', $ips, true)) {
            $hasPrivate = true;
            break;
        }
    }
    if (!$hasPrivate) {
        array_unshift($rules, [
            'type' => 'field',
            'ip' => ['geoip:private'],
            'outboundTag' => 'direct',
        ]);
    }

    $policy = is_array($src['policy'] ?? null) ? $src['policy'] : [
        'levels' => [
            '0' => [
                'handshake' => 4,
                'connIdle' => 120,
                'uplinkOnly' => 30,
                'downlinkOnly' => 30,
            ],
        ],
    ];
    // Xray expects policy.levels as map[uint32]*Policy (JSON object).
    // PHP coerces numeric-string keys ('0') to int (0), and json_encode
    // then serializes such an array as a JSON array — Xray fails on unmarshal:
    //   "cannot unmarshal array into Go struct field PolicyConfig.policy.levels".
    // Casting to (object) forces encoding as a JSON object.
    if (isset($policy['levels']) && is_array($policy['levels'])) {
        $policy['levels'] = (object)$policy['levels'];
    }

    return [
        'log' => [
            'loglevel' => (string)array_get($src, ['log', 'loglevel'], 'warning'),
        ],
        'dns' => $dns,
        'inbounds' => [
            [
                'tag' => 'redir-in',
                'port' => 12345,
                'protocol' => 'dokodemo-door',
                'settings' => [
                    'network' => 'tcp',
                    'followRedirect' => true,
                ],
                'streamSettings' => [
                    'sockopt' => [
                        'tproxy' => 'redirect',
                    ],
                ],
                'sniffing' => [
                    'enabled' => true,
                    'routeOnly' => true,
                    'destOverride' => ['http', 'tls'],
                ],
            ],
            [
                'tag' => 'health-in',
                'listen' => '0.0.0.0',
                'port' => 15443,
                'protocol' => 'socks',
                'settings' => [
                    'auth' => 'noauth',
                    'udp' => false,
                ],
            ],
        ],
        'outbounds' => [
            $proxyOutbound,
            [
                'tag' => 'direct',
                'protocol' => 'freedom',
                'settings' => [
                    'domainStrategy' => 'UseIPv4',
                ],
            ],
            [
                'tag' => 'block',
                'protocol' => 'blackhole',
            ],
        ],
        'routing' => [
            'domainMatcher' => (string)($routing['domainMatcher'] ?? 'hybrid'),
            'domainStrategy' => (string)($routing['domainStrategy'] ?? 'IPIfNonMatch'),
            'rules' => $rules,
        ],
        'policy' => $policy,
    ];
}

$inputJson = $_POST['input_json'] ?? '';
$resultJson = '';
$error = '';
$warnings = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'generate';

    if ($action === 'download') {
        $downloadContent = (string)($_POST['generated_json'] ?? '');
        if ($downloadContent === '') {
            $error = 'Нет сгенерированного JSON для скачивания.';
        } else {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="config.json"');
            header('Content-Length: ' . strlen($downloadContent));
            echo $downloadContent;
            exit;
        }
    } else {
        $decoded = json_decode($inputJson, true);
        if (!is_array($decoded)) {
            $error = 'Не удалось разобрать JSON. Проверьте формат и убедитесь, что вставлен полный JSON-конфиг.';
        } else {
            $proxy = find_proxy_outbound($decoded);
            if ($proxy === []) {
                $warnings[] = 'Не найден явный outbound proxy. Будет создан шаблонный outbound.';
            }
            $resultJson = pretty_json(build_mikrotik_config($decoded));
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta name="robots" content="noindex, nofollow">
<meta charset="utf-8">
<title>Xray → MikroTik config.json generator</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{
    --bg:#0b1020;
    --panel:#121933;
    --soft:#1a2242;
    --text:#e8ecff;
    --muted:#a9b3d9;
    --accent:#7aa2ff;
    --accent-dark:#5d86ea;
    --ok:#33c27f;
    --warn:#ffb347;
    --err:#ff6b6b;
    --border:#2a3566;
    --copy:#2f6fed;
    --copy-dark:#2558bf;
}
*{box-sizing:border-box}
body{
    margin:0;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;
    background:linear-gradient(180deg,#0b1020 0%,#0d1330 100%);
    color:var(--text)
}
.wrap{max-width:1200px;margin:0 auto;padding:24px}
h1{font-size:32px;margin:0 0 10px}
h2{font-size:20px;margin:0 0 12px}
p,li{color:var(--muted);line-height:1.55}
a{color:#b28cff}
.grid{display:grid;grid-template-columns:1.2fr 1fr;gap:20px}
.card{
    background:rgba(18,25,51,.95);
    border:1px solid var(--border);
    border-radius:18px;
    padding:18px;
    box-shadow:0 10px 30px rgba(0,0,0,.25)
}
.card + .card{margin-top:16px}

textarea,
.result-textarea{
    width:100%;
    min-height:420px;
    resize:vertical;
    padding:14px;
    border-radius:14px;
    border:1px solid var(--border);
    background:var(--soft);
    color:var(--text);
    font:13px/1.45 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;
    outline:none;
}

.result-textarea[readonly]{
    white-space:pre;
}

.actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-top:14px
}

button{
    border:0;
    border-radius:12px;
    padding:12px 16px;
    font-weight:700;
    cursor:pointer;
    background:var(--accent);
    color:#08112b;
    transition:.18s ease;
}
button:hover{
    transform:translateY(-1px);
    filter:brightness(1.05);
}
button.secondary{
    background:#293765;
    color:var(--text)
}

.download-btn{
    width:100%;
    font-size:16px;
    padding:13px 16px;
    background:var(--accent);
    color:#08112b;
    margin:0;
}

.copy-btn{
    width:100%;
    font-size:16px;
    padding:13px 16px;
    background:var(--copy);
    color:#fff;
    margin:0;
}

.result-actions{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin:0 0 14px;
}

.result-actions.bottom{
    margin:14px 0 0;
}

.msg{
    border-radius:12px;
    padding:12px 14px;
    margin:0 0 14px
}
.err{
    background:rgba(255,107,107,.12);
    border:1px solid rgba(255,107,107,.35);
    color:#ffd6d6
}
.warn{
    background:rgba(255,179,71,.12);
    border:1px solid rgba(255,179,71,.35);
    color:#ffe3b8
}
.ok{
    background:rgba(51,194,127,.12);
    border:1px solid rgba(51,194,127,.35);
    color:#d9ffe9
}
code,pre{
    font:13px/1.5 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace
}
pre{
    white-space:pre-wrap;
    word-break:break-word;
    background:var(--soft);
    border:1px solid var(--border);
    border-radius:14px;
    padding:14px;
    margin:0
}
.small{font-size:14px}
.kpi{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
.tag{
    display:inline-block;
    border:1px solid var(--border);
    background:#172048;
    border-radius:999px;
    padding:6px 10px;
    font-size:12px;
    color:#cdd7ff
}

.copy-note{
    display:none;
    margin-top:10px;
    font-size:14px;
    color:#b9ffd3;
}

@media (max-width: 960px){
    .grid{grid-template-columns:1fr}
    textarea,.result-textarea{min-height:320px}
}

@media (max-width: 640px){
    .wrap{padding:16px}
    h1{font-size:28px}
    .result-actions{grid-template-columns:1fr}
    .actions{flex-direction:column}
    .actions button{width:100%}
}
</style>
</head>
<body>
<div class="wrap">
    <h1>Xray → MikroTik config.json generator</h1>
    <p class="small">Вставьте экспорт из HAPP или другой Xray JSON. Скрипт соберёт MikroTik-совместимый <code>config.json</code> для transparent proxy: <code>dokodemo-door :12345</code> + health-check на <code>:15443</code>.</p>
    <p>
        Инструкция по подключению XRAY на Mikrotik находится
        <a href="https://github.com/snookerm/mikrotik-xray-failover" target="_blank" rel="noopener noreferrer">
            тут
        </a>
    </p>

    <div class="kpi">
        <span class="tag">PHP 8.1+</span>
        <span class="tag">Single file</span>
        <span class="tag">Download as config.json</span>
        <span class="tag">No external CSS/JS</span>
    </div>

    <div class="grid" style="margin-top:20px">
        <div>
            <div class="card">
                <h2>Входной Xray JSON</h2>
                <?php if ($error !== ''): ?><div class="msg err"><?= h($error) ?></div><?php endif; ?>
                <?php foreach ($warnings as $w): ?><div class="msg warn"><?= h($w) ?></div><?php endforeach; ?>

                <form method="post">
                    <textarea name="input_json" placeholder='Вставьте сюда JSON из HAPP или другой Xray-конфиг'><?= h($inputJson) ?></textarea>
                    <div class="actions">
                        <button type="submit" name="action" value="generate">Сгенерировать</button>
                        <button class="secondary" type="button" onclick="fillExample()">Подставить пример</button>
                        <button class="secondary" type="button" onclick="document.querySelector('[name=input_json]').value=''">Очистить</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Короткая инструкция</h2>
                <ol>
                    <li>Вставьте HAPP/Xray JSON в левое поле.</li>
                    <li>Нажмите <strong>Сгенерировать</strong>.</li>
                    <li>Проверьте результат справа.</li>
                    <li>Нажмите <strong>Скачать config.json</strong>.</li>
                    <li>Загрузите файл на MikroTik в нужную директорию, например <code>xray-configs/config.json</code>.</li>
                </ol>
                <p><strong>Что переносится автоматически:</strong> <code>address</code>, <code>port</code>, <code>id</code>, <code>fingerprint</code>, <code>publicKey</code>, <code>serverName</code>, <code>shortId</code>, а также <code>xhttpSettings</code>, <code>dns</code>, <code>routing</code> и <code>policy</code>, если они есть.</p>
                <p><strong>Что заменяется:</strong> клиентские inbound’ы из HAPP (<code>socks/http</code>) заменяются на MikroTik-совместимые <code>dokodemo-door</code> на <code>12345</code> и health-check inbound на <code>15443</code>.</p>
            </div>
        </div>

        <div>
            <div class="card">
                <h2>Результат</h2>
                <?php if ($resultJson !== ''): ?>
                    <div class="msg ok">Готово. Можно скачать файл <code>config.json</code>.</div>

                    <div class="result-actions">
                        <form method="post">
                            <input type="hidden" name="action" value="download">
                            <input type="hidden" name="generated_json" value="<?= h($resultJson) ?>">
                            <button class="download-btn" type="submit">⬇ Скачать config.json</button>
                        </form>
                        <button class="copy-btn" type="button" onclick="copyResultJson()">📋 Скопировать JSON</button>
                    </div>

                    <textarea id="result_json" class="result-textarea" readonly><?= h($resultJson) ?></textarea>

                    <div class="result-actions bottom">
                        <form method="post">
                            <input type="hidden" name="action" value="download">
                            <input type="hidden" name="generated_json" value="<?= h($resultJson) ?>">
                            <button class="download-btn" type="submit">⬇ Скачать config.json</button>
                        </form>
                        <button class="copy-btn" type="button" onclick="copyResultJson()">📋 Скопировать JSON</button>
                    </div>

                    <div id="copy_note" class="copy-note">JSON скопирован в буфер обмена.</div>
                <?php else: ?>
                    <textarea class="result-textarea" readonly>Здесь появится MikroTik-совместимый config.json.</textarea>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Что важно проверить перед загрузкой на MikroTik</h2>
                <ul>
                    <li>В outbound остался правильный серверный <code>port</code>. Для remote Netwatch используйте тот же порт.</li>
                    <li>Убедитесь, что <code>address</code>, <code>id</code>, <code>publicKey</code>, <code>serverName</code> и <code>shortId</code> заполнены корректно.</li>
                    <li>Если делаете 4 сервера, повторите операцию 4 раза и сохраните файлы как:
                        <code>xray-configs/config.json</code>,
                        <code>xray-configs2/config.json</code>,
                        <code>xray-configs3/config.json</code>,
                        <code>xray-configs4/config.json</code>.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function fillExample() {
    const example = {
        outbounds: [{
            protocol: "vless",
            settings: {
                vnext: [{
                    address: "example.com",
                    port: 8443,
                    users: [{
                        id: "00000000-0000-0000-0000-000000000000",
                        encryption: "none",
                        flow: ""
                    }]
                }]
            },
            streamSettings: {
                network: "xhttp",
                security: "reality",
                realitySettings: {
                    fingerprint: "firefox",
                    publicKey: "REPLACE_ME",
                    serverName: "server.example.com",
                    shortId: "REPLACE_ME",
                    show: false
                },
                xhttpSettings: {
                    host: "",
                    mode: "packet-up",
                    extra: {
                        noGRPCHeader: false,
                        noSSEHeader: false,
                        scMaxBufferedPosts: 2,
                        scMaxEachPostBytes: "500000-1000000",
                        scMinPostsIntervalMs: "100-300",
                        scStreamUpServerSecs: "60-300",
                        xPaddingBytes: "100-240"
                    }
                }
            },
            tag: "proxy"
        }]
    };
    document.querySelector('[name=input_json]').value = JSON.stringify(example, null, 2);
}

function copyResultJson() {
    const el = document.getElementById('result_json');
    if (!el) return;

    const text = el.value;
    const note = document.getElementById('copy_note');

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            if (note) {
                note.style.display = 'block';
                setTimeout(() => note.style.display = 'none', 2200);
            }
        }).catch(() => fallbackCopy(el, note));
    } else {
        fallbackCopy(el, note);
    }
}

function fallbackCopy(el, note) {
    el.removeAttribute('readonly');
    el.select();
    el.setSelectionRange(0, 999999);
    document.execCommand('copy');
    el.setAttribute('readonly', 'readonly');
    window.getSelection()?.removeAllRanges();

    if (note) {
        note.style.display = 'block';
        setTimeout(() => note.style.display = 'none', 2200);
    }
}
</script>
</body>
</html>
