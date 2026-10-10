<?php
// ---- CONFIG: swap this URL for a paid/intraday provider if you need true live ticks ----
const API_URL = 'https://open.er-api.com/v6/latest/USD'; // free, ~160 currencies, updates daily
const REFRESH = 1800; // seconds between stored snapshots

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO('sqlite:' . __DIR__ . '/forex.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS rates(ts INTEGER, cur TEXT, rate REAL);
                    CREATE INDEX IF NOT EXISTS idx ON rates(cur, ts)');
    }
    return $pdo;
}

function collect(bool $force = false): void {
    $db = db();
    $last = (int)$db->query('SELECT MAX(ts) FROM rates')->fetchColumn();
    if (!$force && time() - $last < REFRESH) return;
    $ctx = stream_context_create(['http' => ['timeout' => 8]]);
    $j = json_decode(@file_get_contents(API_URL, false, $ctx), true);
    if (($j['result'] ?? '') !== 'success') return;
    $ts = time();
    $db->beginTransaction();
    $st = $db->prepare('INSERT INTO rates VALUES(?,?,?)');
    foreach ($j['rates'] as $c => $r) $st->execute([$ts, $c, $r]);
    $db->commit();
}

function snapshot(int $ts): array {
    $st = db()->prepare('SELECT cur, rate FROM rates WHERE ts = ?');
    $st->execute([$ts]);
    return $st->fetchAll(PDO::FETCH_KEY_PAIR);
}

function latest(): array {
    $ts = (int)db()->query('SELECT MAX(ts) FROM rates')->fetchColumn();
    return [$ts, $ts ? snapshot($ts) : []];
}

// Snapshot closest to "now - $seconds" (falls back to the oldest one we have)
function past(int $seconds, int $latestTs): array {
    $st = db()->prepare('SELECT MAX(ts) FROM rates WHERE ts <= ?');
    $st->execute([time() - $seconds]);
    $ts = (int)$st->fetchColumn();
    if (!$ts) $ts = (int)db()->query('SELECT MIN(ts) FROM rates')->fetchColumn();
    return ($ts && $ts < $latestTs) ? snapshot($ts) : [];
}

function head(string $title, string $active = ''): void { ?>
    <!doctype html>
    <html lang="en"><head><meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= htmlspecialchars($title) ?> · FX Tracker</title>
        <style>
            :root{--bg:#0f1420;--card:#171e2e;--line:#263049;--tx:#e8ecf5;--mut:#8b96b0;--up:#22c55e;--dn:#ef4444;--ac:#4f8cff}
            *{box-sizing:border-box}body{margin:0;font:15px/1.5 system-ui,sans-serif;background:var(--bg);color:var(--tx)}
            header{display:flex;gap:24px;align-items:center;padding:14px 24px;background:var(--card);border-bottom:1px solid var(--line)}
            header b{font-size:18px}header a{color:var(--mut);text-decoration:none;padding:4px 0}
            header a.on,header a:hover{color:var(--tx);border-bottom:2px solid var(--ac)}
            main{max-width:900px;margin:24px auto;padding:0 16px}
            .bar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-bottom:16px}
            select,input,button{background:var(--card);color:var(--tx);border:1px solid var(--line);border-radius:8px;padding:8px 10px;font:inherit}
            button{background:var(--ac);border:0;cursor:pointer}
            table{width:100%;border-collapse:collapse;background:var(--card);border-radius:10px;overflow:hidden}
            th,td{padding:10px 14px;text-align:left;border-bottom:1px solid var(--line)}th{color:var(--mut);font-weight:500}
            td a{color:var(--tx);text-decoration:none;font-weight:600}.muted{color:var(--mut)}
            .up{color:var(--up)}.dn{color:var(--dn)}
            .card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;margin-bottom:16px}
            .big{font-size:34px;font-weight:700}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
            footer{text-align:center;color:var(--mut);padding:24px;font-size:13px}footer a{color:var(--mut)}
        </style></head><body>
    <header><b>💱 FX Tracker</b>
        <a href="index.php" class="<?= $active==='rates'?'on':'' ?>">Live Rates</a>
        <a href="convert.php" class="<?= $active==='convert'?'on':'' ?>">Converter</a></header><main>
<?php }

function foot(): void { ?>
    </main><footer>Rates by <a href="https://www.exchangerate-api.com">ExchangeRate-API</a> · for information only</footer></body></html>
<?php }